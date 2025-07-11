<?php
    
    namespace App\Models\Repositories;
    
    use Illuminate\Support\Facades\Log;
    use App\Models\Entities\Currency;
    
    class CurrencyRepo {
        public function all() {
            $currency = Currency::whereIn('active', [1,0])->with([])
            ->get();            
            return $currency;
        }
        public function allActive() {
            $currency = Currency::whereIn('active', [1])->with([])
            ->get();            
            return $currency;
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
            $currency = Currency::withTrashed()->get();
            return $currency;
        }
    }