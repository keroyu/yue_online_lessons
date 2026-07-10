<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostImageController extends Controller
{
    /**
     * List images for a post (consumed by the gallery modal).
     */
    public function index(Post $post): JsonResponse
    {
        $images = $post->images()
            ->get()
            ->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->url,
                'filename' => $image->filename,
            ]);

        return response()->json(['images' => $images]);
    }

    /**
     * Batch upload images to a post gallery.
     */
    public function store(Request $request, Post $post): RedirectResponse
    {
        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
        ], [
            'images.required' => '請選擇至少一張圖片',
            'images.max' => '單次最多上傳 20 張',
            'images.*.image' => '檔案必須是圖片',
            'images.*.mimes' => '僅支援 jpg、png、gif、webp',
            'images.*.max' => '單張圖片不可超過 10MB',
        ]);

        foreach (array_reverse($request->file('images')) as $file) {
            $path = $file->store("post-images/{$post->id}", 'public');
            $dimensions = getimagesize($file->getPathname());

            $post->images()->create([
                'path' => $path,
                'filename' => $file->getClientOriginalName(),
                'width' => $dimensions[0] ?? null,
                'height' => $dimensions[1] ?? null,
            ]);
        }

        return back()->with('success', '圖片上傳成功');
    }

    public function destroy(PostImage $image): RedirectResponse
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', '圖片已刪除');
    }
}
