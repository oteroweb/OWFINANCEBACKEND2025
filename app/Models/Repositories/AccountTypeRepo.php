<?php
    
    namespace App\Models\Repositories;
    
    use Illuminate\Support\Facades\Log;
    use App\Models\Entities\AccountType;
    use Illuminate\Support\Str;
    
    class AccountTypeRepo {
        public function all(array $params = []) {
            $query = AccountType::whereIn('active', [1,0])->with([]);
            if (!empty($params['search'])) {
                $this->applyGlobalSearch($query, $params['search'], ['name', 'description', 'icon']);
            }
            $sortBy = $params['sort_by'] ?? 'name';
            $descending = filter_var($params['descending'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $query->orderBy($sortBy, $descending ? 'desc' : 'asc');
            if (!empty($params['page'])) {
                $perPage = $params['per_page'] ?? 15;
                return $query->paginate($perPage);
            }
            return $query->get();
        }
        public function allActive(array $params = []) {
            $query = AccountType::whereIn('active', [1])->with([]);
            if (!empty($params['search'])) {
                $this->applyGlobalSearch($query, $params['search'], ['name', 'description', 'icon']);
            }
            $sortBy = $params['sort_by'] ?? 'name';
            $descending = filter_var($params['descending'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $query->orderBy($sortBy, $descending ? 'desc' : 'asc');
            if (!empty($params['page'])) {
                $perPage = $params['per_page'] ?? 15;
                return $query->paginate($perPage);
            }
            return $query->get();
        }
        public function find($id) {
            $accounttype = AccountType::with([])->find($id);
            return $accounttype;
        }        
        public function store($data) {            
            $accounttype = new AccountType();
            $accounttype->fill($data);
            $accounttype->save();
            return $accounttype;
        }        
        public function update($accounttype, $data) {
            $accounttype->fill($data);
            $accounttype->save();
            return $accounttype;
        }
        public function delete($accounttype, $data) {
            $accounttype->fill($data);
            $accounttype->save();
            return $accounttype;
        }
        public function withTrashed() {
            $accounttype = AccountType::withTrashed()->get();
            return $accounttype;
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