<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Repositories\F3RawPackageRepository;
use App\Models\F3PackageName;
use Illuminate\Foundation\Testing\RefreshDatabase;

class F3RawPackageRepositoryTest extends TestCase
{
  use RefreshDatabase;

  protected F3RawPackageRepository $repo;

  protected function setUp(): void
  {
    parent::setUp();
    $this->repo = new F3RawPackageRepository();
  }

  public function testCreateRawPackage()
  {
    $package = F3PackageName::factory()->create();

    $rawPackage = $this->repo->create([
      'raw_package' => 'Test Package',
      'lead_count' => '16',
      'package_id' => $package->id,
      'dimension' => '3x3',
    ]);

    $this->assertDatabaseHas('f3_raw_packages', [
      'id' => $rawPackage->id,
      'raw_package' => 'Test Package',
    ]);
  }

  public function testCreateWithInvalidPackageIdThrowsException()
  {
    $this->expectException(\InvalidArgumentException::class);

    $this->repo->create([
      'raw_package' => 'Invalid',
      'lead_count' => '10',
      'package_id' => 9999, // non-existent
      'dimension' => '2x2',
    ]);
  }
}
