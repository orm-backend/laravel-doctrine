<?php

namespace ItAces\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

abstract class WebController extends Controller
{
    use \Illuminate\Foundation\Validation\ValidatesRequests;
    
    /**
     *
     * @return array|string
     */
    abstract protected function getDefaultOrder();

    /**
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function addOrders(Request $request, Builder $builder) {
        $orders = $request->input('order');
        
        if (!$orders) {
            $orders = $this->getDefaultOrder();
        }
        
        if (!is_array($orders)) {
            $orders = [$orders];
        }
        
        foreach ($orders as $order) {
            $this->addOrder($builder, $order);
        }
        
        return $builder;
    }
    
    /**
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param string $order
     */
    private function addOrder(Builder $builder, string $order) {
        $direction = 'asc';
        
        if (strpos($order, '-') === 0) {
            $direction = 'desc';
            $order = substr($order, 1);
        }
        
        $order = str_replace('-', '.', $order);
        $builder->orderBy($order, $direction);
    }
    
}