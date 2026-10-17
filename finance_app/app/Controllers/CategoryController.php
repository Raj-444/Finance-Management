<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\Session;
use App\Models\Category;

class CategoryController
{
    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $expenseCategories = Category::getAllForUser($userId, 'expense');
        $incomeCategories = Category::getAllForUser($userId, 'income');

        return View::render('categories.index', [
            'title' => 'Categories',
            'expenseCategories' => $expenseCategories,
            'incomeCategories' => $incomeCategories,
        ], 'app');
    }

    public function store(Request $request): Response
    {
        $userId = (int) Auth::id();
        $name = trim((string)$request->input('name', ''));
        $type = $request->input('type', 'expense');
        $color = $request->input('color', '#64748B');
        $icon = $request->input('icon', 'tag');

        if (empty($name)) {
            Session::setFlash('error', 'Category name is required.');
            return Response::redirect(url('/categories'));
        }

        try {
            Category::create($userId, [
                'name' => $name,
                'type' => $type,
                'color' => $color,
                'icon' => $icon,
            ]);

            Session::setFlash('success', 'Category added.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Failed to add category: ' . $e->getMessage());
        }

        return Response::redirect(url('/categories'));
    }

    public function archive(Request $request, string $id): Response
    {
        $userId = (int) Auth::id();
        $catId = (int) $id;

        Category::archive($catId, $userId);
        Session::setFlash('success', 'Category archived.');
        return Response::redirect(url('/categories'));
    }
}
