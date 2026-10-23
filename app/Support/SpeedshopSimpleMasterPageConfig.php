<?php

namespace App\Support;

trait SpeedshopSimpleMasterPageConfig
{
    abstract protected function masterIndexRouteName(): string;

    abstract protected function masterBasePath(): string;

    protected function masterLayout(): string
    {
        return 'layouts.speedshop';
    }

    /**
     * @return array<string, string>
     */
    protected function masterPageConfig(): array
    {
        $route = $this->masterIndexRouteName();

        return [
            'layout' => $this->masterLayout(),
            'base_url' => url($this->masterBasePath()),
            'route_index' => $route.'.index',
            'route_store' => $route.'.store',
            'route_export' => $route.'.export',
            'route_pdf' => $route.'.pdf',
            'route_import' => $route.'.import',
            'route_template' => $route.'.template',
        ];
    }
}
