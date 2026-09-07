<?php

declare(strict_types=1);

namespace Tests\Rules\Queue\Data;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class Product extends Model
{
}

class Invoice extends Model
{
}

class JobWithoutModelProperty implements ShouldQueue
{
    public function __construct(public int $productId)
    {
    }
}

class ModelHolderNotQueued
{
    public function __construct(public Product $product)
    {
    }
}

class JobWithModelPropertyWithSerializesModels implements ShouldQueue
{
    use SerializesModels;

    public function __construct(public Product $product)
    {
    }
}

abstract class AbstractJobWithModelProperty implements ShouldQueue
{
    public function __construct(public Product $product)
    {
    }
}

class BaseJobWithSerializesModels implements ShouldQueue
{
    use SerializesModels;
}

class JobInheritingSerializesModels extends BaseJobWithSerializesModels
{
    public function __construct(public Product $product)
    {
    }
}

class JobWithProtectedModelProperty implements ShouldQueue
{
    public function __construct(protected Product $product)
    {
    }
}

class JobWithModelPropertyWithoutSerializesModels implements ShouldQueue
{
    public function __construct(public Product $product)
    {
    }
}

class JobWithNullableModelPropertyWithoutSerializesModels implements ShouldQueue
{
    public Product|null $product = null;
}

class JobWithMultipleModelPropertiesWithoutSerializesModels implements ShouldQueue
{
    public function __construct(public Product $product, public Invoice $invoice)
    {
    }
}
