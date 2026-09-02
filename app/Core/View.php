<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    private static string $path = '';

    public static function setPath(string $path): void
    {
        self::$path = rtrim($path, '/');
    }

    /**
     * Render a view inside a layout. Views receive $data as local variables
     * and may set $title / $bodyClass which propagate to the layout.
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): string
    {
        $content = self::partial($view, $data);

        if ($layout === null) {
            return $content;
        }

        return self::partial($layout, array_merge($data, ['content' => $content]));
    }

    public static function partial(string $view, array $data = []): string
    {
        $file = self::$path . '/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View not found: {$view}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } finally {
            $output = ob_get_clean();
        }

        return (string) $output;
    }
}
