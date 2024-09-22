<?php

namespace App\Http\Controllers;

use App\Models\TransactionImage;
use Illuminate\Support\Facades\Log;

class TransactionImageController extends Controller
{
    /**
     *
     * @param  \App\Models\TransactionImage $image
     */
    public function getImageData(TransactionImage $image)
    {
        // @todo verify requested image belonds to current user
        $path = $image->path;
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents(storage_path() . '/app/' . $path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        return response()->json(['base64' => $base64]);
    }
}
