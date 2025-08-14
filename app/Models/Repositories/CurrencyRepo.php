<?php
    
    namespace App\Models\Repositories;
    
    use Illuminate\Support\Facades\Log;
    use App\Models\Entities\Currency;
    
    class CurrencyRepo {
        public function all(array $params = []) {
            $query = Currency::whereIn('active', [1,0])->with([]);

            // search by fields
            if (!empty($params['search'])) {
                $this->applyGlobalSearch($query, $params['search'], ['name', 'code', 'symbol', 'align']);
            }

            // sorting
            $sortBy = $params['sort_by'] ?? 'name';
            $descending = filter_var($params['descending'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $query->orderBy($sortBy, $descending ? 'desc' : 'asc');

            // pagination
            if (!empty($params['page'])) {
                $perPage = $params['per_page'] ?? 15;
                $paginator = $query->paginate($perPage);
                // ensure tax field present
                $paginator->getCollection()->transform(function ($currency) {
                    if (!isset($currency->tax)) $currency->tax = null;
                    return $currency;
                });
                return $paginator;
            }

            $currencies = $query->get();
            foreach ($currencies as $currency) {
                if (!isset($currency->tax)) {
                    $currency->tax = null;
                }
            }
            return $currencies;
        }
        public function allActive(array $params = []) {
            $query = Currency::whereIn('active', [1])->with([]);
            if (!empty($params['search'])) {
                $this->applyGlobalSearch($query, $params['search'], ['name', 'code', 'symbol', 'align']);
            }
            $sortBy = $params['sort_by'] ?? 'name';
            $descending = filter_var($params['descending'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $query->orderBy($sortBy, $descending ? 'desc' : 'asc');
            if (!empty($params['page'])) {
                $perPage = $params['per_page'] ?? 15;
                $paginator = $query->paginate($perPage);
                $paginator->getCollection()->transform(function ($currency) {
                    if (!isset($currency->tax)) $currency->tax = null;
                    return $currency;
                });
                return $paginator;
            }
            $currencies = $query->get();
            foreach ($currencies as $currency) {
                if (!isset($currency->tax)) {
                    $currency->tax = null;
                }
            }
            return $currencies;
        }
        public function find($id) {
            $currency = Currency::with([])->find($id);
            return $currency;
        }        
        public function store($data) {            
            $currency = new Currency();
            $currency->fill($data);
            $currency->save();
            return $currency;
        }        
        public function update($currency, $data) {
            $currency->fill($data);
            $currency->save();
            return $currency;
        }
        public function delete($currency, $data) {
            $currency->fill($data);
            $currency->save();
            return $currency;
        }
        public function withTrashed() {
            $currencies = Currency::withTrashed()->get();
            foreach ($currencies as $currency) {
                if (!isset($currency->tax)) {
                    $currency->tax = null;
                }
            }
            return $currencies;
        }
        private function applyGlobalSearch($query, string $searchTerm, array $fields): void
        {
            $query->where(function ($q) use ($searchTerm, $fields) {
                foreach ($fields as $field) {
                    $q->orWhere($field, 'like', "%{$searchTerm}%");
                }
            });
        }
    }