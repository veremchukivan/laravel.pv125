<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Validator;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     tags={"Product"},
     *     path="/api/product",
     *     @OA\Response(response="200", description="List Products.")
     * )
     */
    public function index()
    {
        $products = Product::with('product_images')->get();
        return response()->json($products, 200);
    }

    /**
     * @OA\Get(
     *     tags={"Product"},
     *     path="/api/product/{id}",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product id",
     *         required=true,
     *         @OA\Schema(
     *             type="number",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(response="200", description="Get Product."),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     )
     * )
     */
    public function getById($id)
    {
        $product = Product::with('product_images')->findOrFail($id);
        return response()->json($product, 200);
    }

    /**
     * @OA\Post(
     *     tags={"Product"},
     *     path="/api/product",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"category_id", "name", "price", "description"},
     *                 @OA\Property(
     *                     property="category_id",
     *                     type="integer"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="price",
     *                     type="number",
     *                     format="float"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(
     *                             property="name",
     *                             type="string"
     *                         ),
     *                         @OA\Property(
     *                             property="priority",
     *                             type="integer"
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="Add Product.")
     * )
     */
    public function store(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required',
            'price' => 'required',
            'description' => 'required',
            'images.*.name' => 'required',
            'images.*.priority' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $product = Product::create($input);

        $imagesData = [];
        if (isset($input['images'])) {
            foreach ($input['images'] as $image) {
                $imageData = [
                    'product_id' => $product->id,
                    'name' => $image['name'],
                    'priority' => $image['priority'],
                ];
                $imagesData[] = $imageData;
            }
        }

        if (!empty($imagesData)) {
            ProductImage::insert($imagesData);
        }

        $product->load('product_images');

        return response()->json($product, 200);
    }

    /**
     * @OA\Post(
     *     tags={"Product"},
     *     path="/api/product/edit/{id}",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="number",
     *             format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"category_id", "name", "price", "description"},
     *                 @OA\Property(
     *                     property="category_id",
     *                     type="integer"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="price",
     *                     type="number",
     *                     format="float"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(
     *                             property="id",
     *                             type="integer"
     *                         ),
     *                         @OA\Property(
     *                             property="name",
     *                             type="string"
     *                         ),
     *                         @OA\Property(
     *                             property="priority",
     *                             type="integer"
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="Update Product.")
     * )
     */
    public function update($id, Request $request)
    {
        $product = Product::findOrFail($id);
        $input = $request->all();

        $validator = Validator::make($input, [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required',
            'price' => 'required',
            'description' => 'required',
            'images.*.id' => 'sometimes|required|exists:product_images,id',
            'images.*.name' => 'required',
            'images.*.priority' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $product->update($input);

        if (isset($input['images'])) {
            $existingImageIds = [];
            $newImagesData = [];

            foreach ($input['images'] as $image) {
                if (isset($image['id'])) {
                    $existingImageIds[] = $image['id'];
                    $productImage = ProductImage::findOrFail($image['id']);
                    $productImage->update([
                        'name' => $image['name'],
                        'priority' => $image['priority'],
                    ]);
                } else {
                    $newImagesData[] = [
                        'product_id' => $product->id,
                        'name' => $image['name'],
                        'priority' => $image['priority'],
                    ];
                }
            }

            ProductImage::where('product_id', $product->id)
                ->whereNotIn('id', $existingImageIds)
                ->delete();

            if (!empty($newImagesData)) {
                ProductImage::insert($newImagesData);
            }
        }

        $product->load('product_images');

        return response()->json($product, 200);
    }

    /**
     * @OA\Delete(
     *     tags={"Product"},
     *     path="/api/product/{id}",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product id",
     *         required=true,
     *         @OA\Schema(
     *             type="number",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(response="204", description="Delete Product."),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(null, 204);
    }
}
