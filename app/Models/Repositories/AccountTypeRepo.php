<?php
    
    namespace App\Models\Repositories;
    
    use Illuminate\Support\Facades\Log;
    use App\Models\Entities\AccountType;
    
    class AccountTypeRepo {
        public function all() {
            $accounttype = AccountType::whereIn('active', [1,0])->with([])
            ->get();            
            return $accounttype;
        }
        public function allActive() {
            $accounttype = AccountType::whereIn('active', [1])->with([])
            ->get();            
            return $accounttype;
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
    }