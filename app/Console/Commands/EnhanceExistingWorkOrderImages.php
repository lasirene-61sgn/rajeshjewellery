<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Storage;

class EnhanceExistingWorkOrderImages extends Command
{
    protected $signature = 'workorders:enhance-images';
    protected $description = 'Upscales and sharpens all existing design images for A4 printing';

    public function handle(): int
    {
        $orders = WorkOrder::whereNotNull('design_image')->get();
        $this->info("Found {$orders->count()} orders to process.");

        foreach ($orders as $order) {
            $path = storage_path('app/public/' . $order->design_image);

            if (! file_exists($path)) {
                continue;
            }

            $info = @getimagesize($path);
            if (! $info) {
                continue;
            }

            $origW = $info[0];
            $origH = $info[1];

            // If already high-res, skip
            if ($origW >= 1800) {
                continue;
            }

            $mime = $info['mime'] ?? 'image/jpeg';
            $src = match ($mime) {
                'image/png'  => @imagecreatefrompng($path),
                'image/webp' => @imagecreatefromwebp($path),
                default      => @imagecreatefromjpeg($path)
            };

            if (! $src) {
                continue;
            }

            $targetW = 1800;
            $targetH = (int) round(($origH / $origW) * $targetW);

            $canvas = imagecreatetruecolor($targetW, $targetH);
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $targetW, $targetH, $white);

            imagecopyresampled($canvas, $src, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);
            imagedestroy($src);

            // Sharpen
            $sharpenMatrix = [
                [-1, -1, -1],
                [-1, 16, -1],
                [-1, -1, -1]
            ];
            imageconvolution($canvas, $sharpenMatrix, 8, 0);

            // Overwrite existing file with high-quality JPEG
            imagejpeg($canvas, $path, 95);
            imagedestroy($canvas);

            $this->line("Enhanced order #{$order->id}");
        }

        $this->info('Completed all image enhancements.');
        return Command::SUCCESS;
    }
}
