<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Repositories\F3PackageRepository;
use App\Models\F3PackageName;
use Illuminate\Foundation\Testing\RefreshDatabase;

class F3PackageRepositoryTest extends TestCase
{
  // run this test
  // php artisan test --filter=ParseDateTraitTest
  use RefreshDatabase;

  protected F3PackageRepository $repo;

  protected function setUp(): void
  {
    parent::setUp();
    $this->repo = new F3PackageRepository();
  }

  public function testCreatePackage()
  {
    $data = ['package_name' => 'test only'];
    $package = $this->repo->create($data);

    $this->assertInstanceOf(F3PackageName::class, $package);
    $this->assertDatabaseHas('f3_package_names', ['package_name' => 'test only']);
  }

  public function testUpdatePackage()
  {
    $package = F3PackageName::factory()->create(['package_name' => 'test Old Name']);
    $updated = $this->repo->update($package->id, ['package_name' => 'test New Name']);

    $this->assertTrue($updated);
    $this->assertDatabaseHas('f3_package_names', ['package_name' => 'test New Name']);
  }

  public function testDeletePackage()
  {
    $package = F3PackageName::factory()->create();
    $deleted = $this->repo->delete($package->id);

    $this->assertTrue($deleted);
    $this->assertDatabaseMissing('f3_package_names', ['id' => $package->id]);
  }
}
