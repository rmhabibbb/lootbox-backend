<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Exception;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function all(Request $request)
    {
        try {
            $id = $request->input('id');
            $limit = $request->input('limit');
            $name = $request->input('name');
            $show_product = $request->input('show_product');


            if ($id) {
                $category = ProductCategory::with(['products'])->find($id);

                if ($category) {
                    return ResponseFormatter::success(
                        $category,
                        'Data kategori berhasil diambil'
                    );
                } else {
                    return ResponseFormatter::error(
                        null,
                        'Data kategori tidak ditemukan'
                    );
                }
            }

            $categories = ProductCategory::query();

            if ($name)
                $categories->where('name', 'like', '%' . $name . '%');

            if ($show_product)
                $categories->with(['products']);

            return ResponseFormatter::success(
                $categories->paginate($limit),
                'Data kategori berhasil diambil'
            );
        } catch (Exception $e) {
            return ResponseFormatter::error(
                null,
                (env('APP_DEBUG')) ?  $e->getMessage() : 'Terjadi Kesalahan',
                500
            );
        }
    }
}
