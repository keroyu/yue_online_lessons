<?php

namespace App\Services;

use League\CommonMark\CommonMarkConverter;

/**
 * Markdown → HTML for admin-written email bodies (011 FR-021 / D22).
 *
 * CommonMark swallows a single newline, so a line the admin broke in the
 * textarea came out joined in the delivered mail — they had to press Enter
 * twice for anything to happen, while the editor preview (marked with
 * `breaks: true`) showed the break they expected. Hard breaks make the two
 * agree; a blank line is still a new paragraph.
 *
 * Shared by every path that renders admin-typed Markdown into a mail body:
 * EmailTemplate::renderBody(), BatchEmailMail, SendDripEmailJob. Blog posts
 * (PostService) deliberately keep CommonMark's paragraph semantics.
 */
class EmailMarkdownService
{
    public static function toHtml(string $md): string
    {
        $converter = new CommonMarkConverter([
            'renderer' => ['soft_break' => "<br />\n"],
        ]);

        return $converter->convert($md)->getContent();
    }
}
