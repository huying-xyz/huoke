<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use think\Request;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class Qr
{
    public function generate(Request $request)
    {
        $text   = $request->param('text');
        $size   = (int)$request->param('size', 300);
        $margin = (int)$request->param('margin', 4);
        $ecc    = strtoupper($request->param('ecc', 'L'));
        $output = strtolower($request->param('output', 'png'));

        if (!$text) {
            return json(['code' => 400, 'message' => '缺少 text 参数']);
        }

        $eccLevels = [
            'L' => QRCode::ECC_L,
            'M' => QRCode::ECC_M,
            'Q' => QRCode::ECC_Q,
            'H' => QRCode::ECC_H,
        ];

        $options = new QROptions([
            'outputType'    => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'      => $eccLevels[$ecc],
            'scale'         => 10,
            'quietzoneSize' => $margin,
        ]);

        $qrcode = new QRCode($options);
        $imageData = $qrcode->render($text);

        $src   = imagecreatefromstring(base64_decode(explode(',', $imageData)[1]));
        $origW = imagesx($src);
        $origH = imagesy($src);
        $targetW = $targetH = $size;

        $dst = imagecreatetruecolor($targetW, $targetH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);

        imagecopyresized($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);

        ob_start();
        imagepng($dst);
        $final = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        if ($output === 'base64') {
            return json(['code' => 200, 'data' => 'data:image/png;base64,' . base64_encode($final)]);
        }

        header('Content-Type: image/png');
        echo $final;
        exit;
    }
}