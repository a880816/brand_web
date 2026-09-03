<?php
namespace App\Support;
use App\Models\Brand;
class BrandContext { private ?Brand $brand=null; public function set(Brand $brand): void{$this->brand=$brand;} public function brand(): Brand{ return $this->brand ?? throw new \LogicException('Brand has not been resolved.'); } public function id(): int{return $this->brand()->id;} }
