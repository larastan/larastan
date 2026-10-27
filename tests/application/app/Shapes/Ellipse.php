<?php

namespace App\Shapes;

use Database\Factories\Shapes\EllipseFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(EllipseFactory::class)]
class Ellipse extends Model
{
    /** @use HasFactory<EllipseFactory> */
    use HasFactory;
}
