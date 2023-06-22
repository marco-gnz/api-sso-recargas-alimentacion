<?php

namespace App\Http\Controllers\Admin\Modulos;

use App\Http\Controllers\Controller;
use App\Models\Ley;
use Illuminate\Http\Request;

class ModulosController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function leyes()
    {
        $leyes = Ley::orderBy('nombre', 'desc')->get();
    }
}
