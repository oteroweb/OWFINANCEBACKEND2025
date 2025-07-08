<?php
    
    namespace App\Http\Models\Repositories;
    
    use Mockery\Matcher\Type;
    use Illuminate\Support\Facades\Log;
    use App\Http\Models\Entities\Currency;
    
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
    }