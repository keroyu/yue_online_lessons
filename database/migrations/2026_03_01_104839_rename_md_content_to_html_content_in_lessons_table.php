<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    // This migration was superseded by the HTML→Markdown refactor on main
    // (2026_02_28_rename_html_content_to_markdown_columns.php).
    // The codebase uses md_content; no column rename needed.

    public function up(): void {}

    public function down(): void {}
};
