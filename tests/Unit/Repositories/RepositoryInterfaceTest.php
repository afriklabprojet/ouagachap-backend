<?php

namespace Tests\Unit\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour RepositoryInterface
 */
class RepositoryInterfaceTest extends TestCase
{
    /** @test */
    public function interface_exists(): void
    {
        $this->assertTrue(interface_exists(RepositoryInterface::class));
    }

    /** @test */
    public function interface_declares_all_method(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        
        $this->assertTrue($reflection->hasMethod('all'));
        $method = $reflection->getMethod('all');
        $this->assertTrue($method->isPublic());
    }

    /** @test */
    public function interface_declares_find_method(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        
        $this->assertTrue($reflection->hasMethod('find'));
        $method = $reflection->getMethod('find');
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
    }

    /** @test */
    public function interface_declares_find_or_fail_method(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        
        $this->assertTrue($reflection->hasMethod('findOrFail'));
        $method = $reflection->getMethod('findOrFail');
        $this->assertTrue($method->isPublic());
    }

    /** @test */
    public function interface_declares_create_method(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        
        $this->assertTrue($reflection->hasMethod('create'));
        $method = $reflection->getMethod('create');
        $this->assertTrue($method->isPublic());
    }

    /** @test */
    public function interface_declares_update_method(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        
        $this->assertTrue($reflection->hasMethod('update'));
        $method = $reflection->getMethod('update');
        $this->assertTrue($method->isPublic());
        $this->assertCount(2, $method->getParameters());
    }

    /** @test */
    public function interface_declares_delete_method(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        
        $this->assertTrue($reflection->hasMethod('delete'));
        $method = $reflection->getMethod('delete');
        $this->assertTrue($method->isPublic());
    }

    /** @test */
    public function interface_declares_paginate_method(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        
        $this->assertTrue($reflection->hasMethod('paginate'));
        $method = $reflection->getMethod('paginate');
        $this->assertTrue($method->isPublic());
    }

    /** @test */
    public function all_method_returns_collection(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('all');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals(Collection::class, $returnType->getName());
    }

    /** @test */
    public function find_method_returns_nullable_model(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('find');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
    }

    /** @test */
    public function find_or_fail_method_returns_model(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('findOrFail');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals(Model::class, $returnType->getName());
    }

    /** @test */
    public function create_method_returns_model(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('create');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals(Model::class, $returnType->getName());
    }

    /** @test */
    public function update_method_returns_model(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('update');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals(Model::class, $returnType->getName());
    }

    /** @test */
    public function delete_method_returns_bool(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('delete');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    /** @test */
    public function paginate_method_returns_paginator(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('paginate');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals(LengthAwarePaginator::class, $returnType->getName());
    }

    /** @test */
    public function find_accepts_int_or_string_id(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('find');
        $params = $method->getParameters();
        
        $this->assertCount(1, $params);
        $idParam = $params[0];
        $type = $idParam->getType();
        
        // Union type: int|string
        $this->assertInstanceOf(\ReflectionUnionType::class, $type);
    }

    /** @test */
    public function paginate_has_default_per_page(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('paginate');
        $params = $method->getParameters();
        
        $perPageParam = $params[0];
        $this->assertTrue($perPageParam->isDefaultValueAvailable());
        $this->assertEquals(15, $perPageParam->getDefaultValue());
    }

    /** @test */
    public function create_method_accepts_array_data(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('create');
        $params = $method->getParameters();
        
        $this->assertCount(1, $params);
        $dataParam = $params[0];
        $this->assertEquals('array', $dataParam->getType()->getName());
    }

    /** @test */
    public function update_method_accepts_id_and_data(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('update');
        $params = $method->getParameters();
        
        $this->assertCount(2, $params);
        $this->assertEquals('id', $params[0]->getName());
        $this->assertEquals('data', $params[1]->getName());
    }
}
