<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The two images that identify the site: the navbar logo and the favicon.
 *
 * Both used to be files in the repo (`resources/images/og-logo.png` imported by
 * Vite, and an empty `public/favicon.ico`), which is fine for one install and
 * useless for a second one — a customer cannot replace an asset that is baked
 * into the JS bundle. They are settings now, stored on the public disk.
 *
 * The favicon is uploaded once as a PNG and fanned out here: browsers ask for
 * several sizes and one of them (`/favicon.ico`) is requested by name whether
 * the page declares it or not, so leaving that to the uploader means half the
 * clients get nothing.
 */
class SiteIconService
{
    public const LOGO_KEY = 'site_logo_path';

    public const FAVICON_KEY = 'site_favicon_path';

    public const FAVICON_APPLE_KEY = 'site_favicon_apple_path';

    public const FAVICON_ICO_KEY = 'site_favicon_ico_path';

    /** Where every generated file lives, relative to the public disk. */
    private const DIR = 'site-icons';

    public function storeLogo(UploadedFile $file): string
    {
        $this->deleteLogo();

        $path = $file->store(self::DIR, 'public');
        SiteSetting::set(self::LOGO_KEY, $path);

        return $path;
    }

    public function deleteLogo(): void
    {
        $this->forget(self::LOGO_KEY);
    }

    /**
     * Store the source image and derive the three files a browser may ask for.
     *
     * Sizes are square and the source is drawn into them without preserving the
     * aspect ratio on purpose: a favicon slot is square, and a letterboxed icon
     * reads as a mistake at 32px. The admin hint says to upload a square image.
     */
    public function storeFavicon(UploadedFile $file): void
    {
        $this->deleteFavicon();

        $source = $this->readImage($file->getRealPath());

        $png32 = $this->resizeToPng($source, 32);
        $png180 = $this->resizeToPng($source, 180);

        $stamp = substr(sha1($png32), 0, 8);
        $disk = Storage::disk('public');

        $paths = [
            self::FAVICON_KEY       => self::DIR . "/favicon-{$stamp}-32.png",
            self::FAVICON_APPLE_KEY => self::DIR . "/favicon-{$stamp}-180.png",
            self::FAVICON_ICO_KEY   => self::DIR . "/favicon-{$stamp}.ico",
        ];

        $disk->put($paths[self::FAVICON_KEY], $png32);
        $disk->put($paths[self::FAVICON_APPLE_KEY], $png180);
        $disk->put($paths[self::FAVICON_ICO_KEY], $this->wrapPngInIco($png32, 32));

        foreach ($paths as $key => $path) {
            SiteSetting::set($key, $path);
        }
    }

    public function deleteFavicon(): void
    {
        foreach ([self::FAVICON_KEY, self::FAVICON_APPLE_KEY, self::FAVICON_ICO_KEY] as $key) {
            $this->forget($key);
        }
    }

    /** Absolute URLs for the layout, or null where nothing has been uploaded. */
    public function urls(): array
    {
        $values = SiteSetting::getMany([
            self::LOGO_KEY,
            self::FAVICON_KEY,
            self::FAVICON_APPLE_KEY,
            self::FAVICON_ICO_KEY,
        ]);

        $url = fn (string $key) => ($p = trim((string) $values->get($key, ''))) !== ''
            ? Storage::disk('public')->url($p)
            : null;

        return [
            'logo'         => $url(self::LOGO_KEY),
            'favicon'      => $url(self::FAVICON_KEY),
            'appleIcon'    => $url(self::FAVICON_APPLE_KEY),
            'faviconIco'   => $url(self::FAVICON_ICO_KEY),
        ];
    }

    /** Absolute path of the uploaded logo, for GD to composite (OG cards). */
    public function logoPath(): ?string
    {
        $path = trim((string) SiteSetting::get(self::LOGO_KEY, ''));
        if ($path === '') {
            return null;
        }

        $disk = Storage::disk('public');

        return $disk->exists($path) ? $disk->path($path) : null;
    }

    private function forget(string $key): void
    {
        $path = trim((string) SiteSetting::get($key, ''));

        if ($path !== '') {
            Storage::disk('public')->delete($path);
        }

        SiteSetting::set($key, '');
    }

    /** @return \GdImage */
    private function readImage(string $path)
    {
        $data = (string) file_get_contents($path);
        $image = imagecreatefromstring($data);

        if ($image === false) {
            throw new \RuntimeException('無法讀取圖片');
        }

        return $image;
    }

    /** @param \GdImage $source */
    private function resizeToPng($source, int $size): string
    {
        $canvas = imagecreatetruecolor($size, $size);

        // Transparency has to be set up before the copy, not after: without
        // these three calls GD flattens the alpha channel onto black, which is
        // exactly what a logo on a light browser tab must not do.
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

        imagecopyresampled(
            $canvas, $source,
            0, 0, 0, 0,
            $size, $size, imagesx($source), imagesy($source)
        );

        ob_start();
        imagepng($canvas, null, 9);
        return (string) ob_get_clean();
    }

    /**
     * A real .ico containing one PNG image.
     *
     * GD cannot write ICO and this box has no Imagick, but the format does not
     * need either: since Vista an icon entry may hold a PNG byte-for-byte, so
     * the whole file is a 6-byte header, one 16-byte directory entry and the
     * PNG. Every browser in use reads it.
     */
    private function wrapPngInIco(string $png, int $size): string
    {
        // 0 = reserved, 1 = type "icon", 1 = number of images.
        $header = pack('vvv', 0, 1, 1);

        $entry = pack(
            'CCCCvvVV',
            $size === 256 ? 0 : $size, // width (0 means 256)
            $size === 256 ? 0 : $size, // height
            0,                         // palette size, 0 for non-paletted
            0,                         // reserved
            1,                         // colour planes
            32,                        // bits per pixel
            strlen($png),              // size of the image data
            6 + 16                     // offset: header + this entry
        );

        return $header . $entry . $png;
    }
}
