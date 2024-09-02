<?php

namespace Ugly\SearchModel;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        Builder::mixin(new BuilderMixin);
    }
}
