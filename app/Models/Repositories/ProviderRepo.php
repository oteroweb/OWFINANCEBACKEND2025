<?php
    
    namespace App\Http\Models\Repositories;
    
    use Mockery\Matcher\Type;
    use Illuminate\Support\Facades\Log;
    use App\Http\Models\Entities\Provider;
    
    class ProviderRepo {
        public function all() {
            $provider = Provider::whereIn('active', [1,0])->with([])
            ->get();            
            return $provider;
        }
        public function allActive() {
            $provider = Provider::whereIn('active', [1])->with([])
            ->get();            
            return $provider;
        }
        public function find($id) {
            $provider = Provider::with([])->find($id);
            return $provider;
        }        
        public function store($data) {            
            $provider = new Provider();
            $provider->fill($data);
            $provider->save();
            return $provider;
        }        
        public function update($provider, $data) {
            $provider->fill($data);
            $provider->save();
            return $provider;
        }
        public function delete($provider, $data) {
            $provider->fill($data);
            $provider->save();
            return $provider;
        }
    }