<?php

use App\Traits\HasPromo;
use Livewire\Component;
use Livewire\Livewire;

class DummyPromoComponent extends Component {
    use HasPromo;
    public $promo = '';
    public $promo_is_valid = false;
    public function render() {
        return <<<'blade'
        <div></div>
        blade;
    }
}

it('sets promo_is_valid to true when correct promo is entered', function () {
    config()->set('company.use-promo-code', true);
    config()->set('company.promo-code', 'promo123');

    Livewire::test(DummyPromoComponent::class)
        ->set('promo', 'PROMO123')
        ->assertSet('promo_is_valid', true);
});

it('leaves promo_is_valid as false when invalid promo is entered', function () {
    config()->set('company.use-promo-code', true);
    config()->set('company.promo-code', 'promo123');

    Livewire::test(DummyPromoComponent::class)
        ->set('promo', 'wrongcode')
        ->assertSet('promo_is_valid', false);
});
