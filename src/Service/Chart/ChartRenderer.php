<?php

namespace App\Service\Chart;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

/**
 * Renderiza una configuración de Chart.js a PNG usando wkhtmltoimage.
 *
 * Se inyecta Chart.js 2.9.4 (compatible con el WebKit antiguo de wkhtmltoimage)
 * dentro de un HTML temporal y se captura con animaciones desactivadas.
 */
final class ChartRenderer
{
    public function __construct(
        #[Autowire('%env(WKHTMLTOIMAGE_BIN)%')]
        private readonly string $binary,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @param array<string, mixed> $config configuración de Chart.js (type, data, options)
     *
     * @return string ruta del PNG generado (el llamador debe borrarlo al terminar)
     */
    public function render(array $config, int $width = 840, int $height = 470): string
    {
        $html = $this->buildHtml($config, $width, $height);

        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('chart_', true);
        $htmlFile = $base . '.html';
        $pngFile = $base . '.png';
        file_put_contents($htmlFile, $html);

        $process = new Process([
            $this->binary,
            '--quiet',
            '--enable-javascript',
            '--javascript-delay', '900',
            '--no-stop-slow-scripts',
            '--disable-smart-width',
            '--width', (string) $width,
            '--quality', '92',
            '--format', 'png',
            $htmlFile,
            $pngFile,
        ]);
        $process->setTimeout(30);
        $process->run();

        @unlink($htmlFile);

        if (!$process->isSuccessful() || !is_file($pngFile) || filesize($pngFile) === 0) {
            @unlink($pngFile);
            throw new \RuntimeException('wkhtmltoimage falló: ' . $process->getErrorOutput());
        }

        return $pngFile;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function buildHtml(array $config, int $width, int $height): string
    {
        $chartJs = file_get_contents($this->projectDir . '/assets/charts/Chart.min.js');
        $configJson = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  html, body { margin: 0; padding: 0; background: #ffffff; }
  #wrap { width: {$width}px; height: {$height}px; padding: 14px; box-sizing: border-box; }
</style>
<script>{$chartJs}</script>
</head>
<body>
<div id="wrap"><canvas id="c" width="{$width}" height="{$height}"></canvas></div>
<script>
  var cfg = {$configJson};
  cfg.options = cfg.options || {};
  cfg.options.animation = false;
  cfg.options.responsive = false;
  cfg.options.devicePixelRatio = 2;
  new Chart(document.getElementById('c').getContext('2d'), cfg);
</script>
</body>
</html>
HTML;
    }
}
