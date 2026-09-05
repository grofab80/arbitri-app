<?php
namespace Api\Controllers;

use Api\V1\Response;
use Api\Models\Category;

class CategoryController {

    public function children(): void
    {

        $parent = $_GET['parent_id'] ?? null;

        if ($parent === null) {
            Response::ok(Category::roots());
        } else {
            Response::ok(Category::children((int)$parent));
        }
    }

    public function show(): void
    {

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            Response::error('ID categoria mancante');
        }

        $cat = Category::find($id);

        if (!$cat) {
            Response::error('Categoria non trovata', 404);
        }

        Response::ok($cat);
    }

    public function tree(): void
    {

        Response::ok(Category::tree());
    }

}
