<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index() {
        $list = Category::all();
        return response()->json($list, 200,
            ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE);
    }
    public function store(Request $request)
    {
        // Отримати дані з запиту і зберегти нову категорію
        $category = new Category;
        $category->name = $request->input('name');
        // Збереження інших полів категорії
        $category->save();

        return response()->json(['message' => 'Категорія успішно створена']);
    }

    public function update(Request $request, $id)
    {
        // Знайти категорію за ідентифікатором
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Категорія не знайдена'], 404);
        }

        // Оновити поля категорії
        $category->name = $request->input('name');
        // Оновлення інших полів категорії
        $category->save();

        return response()->json(['message' => 'Категорія успішно оновлена']);
    }

    public function destroy($id)
    {
        // Знайти категорію за ідентифікатором
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Категорія не знайдена'], 404);
        }

        // Видалити категорію
        $category->delete();

        return response()->json(['message' => 'Категорія успішно видалена']);
    }
}
