<?php

namespace App\Services;

use App\Repositories\Province\ProvinceRepository;

class ProvinceService
{
    private ProvinceRepository $provinceRepository;

    public function __construct(ProvinceRepository $provinceRepository)
    {
        $this->provinceRepository = $provinceRepository;
    }

    public function getAll()
    {
        return $this->provinceRepository->getAll();
    }

    public function countStoreByFilter($request)
    {
        return $this->provinceRepository->countStoreByFilter($request);
    }

    public function getNameProvinceById($id)
    {
        return $this->provinceRepository->getNameProvinceById($id);
    }
}
