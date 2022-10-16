<?php

namespace App\Repositories\Province;

use App\Enums\EnumStore;
use App\Models\Province;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class ProvinceRepository extends BaseRepository implements ProvinceRepositoryInterface
{

    public function getModel(): string
    {
        return Province::class;
    }

    public function getAll()
    {
        return $this->model->orderBy('order_number')->get();
    }

    public function countStoreByFilter($request)
    {
        $keyWord = $request['keyword'] ?? null;
        $provinceId = $request['province_id'] ?? [];
        return $this->model->select('id', 'name')
            ->withCount([
                'stores' => function ($query) use ($keyWord, $provinceId) {
                    $query->where('status', EnumStore::STATUS_CONFIRMED)
                        ->when($keyWord, function ($query) use ($keyWord) {
                            return $query->where('dtb_stores.name', 'like', '%' . $keyWord . '%');
                        })
                        ->when($provinceId, function ($query) use ($provinceId) {
                            return $query->whereIn('dtb_stores.province_id', $provinceId);
                        });
                }
            ])
            ->orderBy('order_number')
            ->get();
    }

    public function getNameProvinceById($id)
    {
        $result = $this->model->select('name')->where('id', $id)->first();
        if ($result) {
            return $result->name;
        }
        return '';
    }
}
