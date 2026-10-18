<?php
namespace SI\SimpleForms\Admin;

defined('ABSPATH') || exit;

final class PhpCodeInspectorPage
{
    public function register()
    {
    }

    private function phpFiles()
    {
        $files = [];
        $dir = new \RecursiveDirectoryIterator(SI_SIMPLE_FORMS_DIR, \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($dir);
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = str_replace(SI_SIMPLE_FORMS_DIR, '', $file->getPathname());
            }
        }
        sort($files);
        return $files;
    }

    private function checkSyntax($absolutePath)
    {
        $code = file_get_contents($absolutePath);
        if ($code === false) {
            return ['ok' => false, 'message' => __('Unable to read file.', 'si-simple-forms')];
        }

        // PHP tokenizer parse check (catches most fatal syntax errors).
        // Intentionally does not shell out to `php -l`: WordPress.org plugins
        // must not invoke shell commands, and most hosts disable shell_exec anyway.
        try {
            if (defined('TOKEN_PARSE')) {
                token_get_all($code, TOKEN_PARSE);
            } else {
                token_get_all($code);
            }
            return ['ok' => true, 'message' => __('No syntax errors detected (tokenizer check).', 'si-simple-forms')];
        } catch (\ParseError $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function render()
    {
        $files = $this->phpFiles();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only file-tree selector; validated against the whitelist of $files below before use.
        $selected = isset($_GET['file']) ? sanitize_text_field(wp_unslash($_GET['file'])) : null;
        $selected = $selected && in_array($selected, $files, true) ? $selected : null;

        Layout::open('si-simple-forms-inspector', __('PHP Code Inspector', 'si-simple-forms'), __('Browse plugin source files and run a live syntax health check across the codebase.', 'si-simple-forms'));

        echo '<div class="si-grid" style="grid-template-columns:320px 1fr;align-items:start;gap:18px;">';

        echo '<div class="si-card">';
        echo '<h2 style="margin:0 0 6px;font-size:14px;">' . esc_html__('Plugin Files', 'si-simple-forms') . ' (' . count($files) . ')</h2>';
        echo '<ul class="si-file-tree">';
        foreach ($files as $f) {
            $ok = $this->checkSyntax(SI_SIMPLE_FORMS_DIR . $f);
            $isActive = ($f === $selected);
            echo '<li' . ($isActive ? ' style="background:#eef2ff;border-radius:6px;padding-left:6px;"' : '') . '>';
            echo '<a class="si-file-path" style="text-decoration:none;color:' . ($isActive ? '#4338ca' : '#334155') . ';font-weight:' . ($isActive ? '700' : '500') . ';" href="' . esc_url(admin_url('admin.php?page=si-simple-forms-inspector&file=' . rawurlencode($f))) . '">' . esc_html($f) . '</a>';
            echo '<span class="' . ($ok['ok'] ? 'si-status-ok' : 'si-status-warn') . '">' . ($ok['ok'] ? '&#10003;' : '&#9888;') . '</span>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';

        echo '<div>';
        if ($selected) {
            $abs = SI_SIMPLE_FORMS_DIR . $selected;
            $result = $this->checkSyntax($abs);
            echo '<div class="si-card" style="margin-bottom:14px;">';
            echo '<h2 style="margin:0 0 8px;font-size:14px;">' . esc_html($selected) . '</h2>';
            echo '<p class="' . ($result['ok'] ? 'si-status-ok' : 'si-status-warn') . '" style="margin:0;">' . ($result['ok'] ? '&#10003; ' : '&#9888; ') . esc_html($result['message']) . '</p>';
            echo '</div>';
            $code = file_get_contents($abs);
            echo '<div class="si-code-view">' . esc_html($code) . '</div>';
        } else {
            $totalOk = 0;
            foreach ($files as $f) {
                if ($this->checkSyntax(SI_SIMPLE_FORMS_DIR . $f)['ok']) {
                    $totalOk++;
                }
            }
            echo '<div class="si-card">';
            echo '<h2 style="margin:0 0 8px;font-size:15px;">' . esc_html__('Codebase Health Summary', 'si-simple-forms') . '</h2>';
            /* translators: %1$d: number of files that passed syntax validation. %2$d: total number of PHP files scanned. */
            echo '<p style="font-size:13px;color:#475569;">' . sprintf(esc_html__('%1$d of %2$d PHP files passed syntax validation.', 'si-simple-forms'), (int) $totalOk, count($files)) . '</p>';
            echo '<p style="font-size:12.5px;color:#94a3b8;">' . esc_html__('Select a file on the left to view its full source and inspection result.', 'si-simple-forms') . '</p>';
            echo '</div>';
        }
        echo '</div>';

        echo '</div>';

        Layout::close();
    }
}
