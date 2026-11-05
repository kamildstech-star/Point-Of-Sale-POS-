@extends('layouts.frontend_master')

@section('meta')
  @include("frontend.all-products-tags")
@endsection

@section('css')
@endsection

@section('content')
    <main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
        <div id="shopify-section-template--16988185755808__banner" class="shopify-section section">

            <div class="collection-hero color-background-1 gradient">
                <div class="collection-hero__inner page-width">
                    <div class="collection-hero__text-wrapper">
                        <h1 class="collection-hero__title">
                            <span class="visually-hidden">Collection: </span>Products
                        </h1>
                        <div class="collection-hero__description rte"></div>
                    </div>
                </div>
            </div>
        </div>
        <div id="shopify-section-template--16988185755808__product-grid" class="shopify-section section">
            <div class="section-template--16988185755808__product-grid-padding">
                <div class="">
                    <aside aria-labelledby="verticalTitle" class="facets-wrapper page-width" id="main-collection-filters"
                        data-id="template--16988185755808__product-grid">

                        <div class="facets-container">
                            <facet-filters-form class="facets small-hide">
                                <form id="FacetFiltersForm" class="facets__form">
                                    <div id="FacetsWrapperDesktop" class="facets__wrapper">
                                        <noscript>
                                            <button type="submit" class="facets__button-no-js button button--secondary">
                                                Filter
                                            </button>
                                        </noscript>
                                    </div>

                                    <div class="active-facets active-facets-desktop">
                                        <facet-remove class="active-facets__button-wrapper">
                                            <a href="all.html" class="active-facets__button-remove underlined-link">
                                                <span>Remove all</span>
                                            </a>
                                        </facet-remove>
                                    </div>

                                    <div class="facet-filters sorting caption">
                                        <div class="facet-filters__field">
                                            <h2 class="facet-filters__label caption-large text-body">
                                                <label for="SortBy">Sort by:</label>
                                            </h2>
                                            <div class="select">
                                                <select name="sort_by"
                                                    class="facet-filters__sort select__select caption-large" id="SortBy"
                                                    aria-describedby="a11y-refresh-page-message">
                                                    <option value="manual">Featured</option>
                                                    <option value="best-selling">Best selling</option>
                                                    <option value="title-ascending" selected>
                                                        Alphabetically, A-Z
                                                    </option>
                                                    <option value="title-descending">
                                                        Alphabetically, Z-A
                                                    </option>
                                                    <option value="price-ascending">
                                                        Price, low to high
                                                    </option>
                                                    <option value="price-descending">
                                                        Price, high to low
                                                    </option>
                                                    <option value="created-ascending">
                                                        Date, old to new
                                                    </option>
                                                    <option value="created-descending">
                                                        Date, new to old
                                                    </option>
                                                </select>
                                                <svg aria-hidden="true" focusable="false" class="icon icon-caret"
                                                    viewbox="0 0 10 6">
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                        d="M9.354.646a.5.5 0 00-.708 0L5 4.293 1.354.646a.5.5 0 00-.708.708l4 4a.5.5 0 00.708 0l4-4a.5.5 0 000-.708z"
                                                        fill="currentColor"></path>
                                                </svg>
                                            </div>
                                        </div>

                                        <noscript>
                                            <button type="submit" class="facets__button-no-js button button--secondary">
                                                Sort
                                            </button>
                                        </noscript>
                                    </div>
                                    <div class="product-count light" role="status">
                                        <h2 class="product-count__text text-body">
                                            <span id="ProductCountDesktop">6714 products </span>
                                        </h2>
                                        <div class="loading-overlay__spinner">
                                            <svg aria-hidden="true" focusable="false" class="spinner" viewbox="0 0 66 66"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <circle class="path" fill="none" stroke-width="6" cx="33"
                                                    cy="33" r="30"></circle>
                                            </svg>
                                        </div>
                                    </div>
                                </form>
                            </facet-filters-form>

                            <menu-drawer class="mobile-facets__wrapper medium-hide large-up-hide" data-breakpoint="mobile">
                                <details class="mobile-facets__disclosure disclosure-has-popup">
                                    <summary class="mobile-facets__open-wrapper focus-offset">
                                        <span class="mobile-facets__open">
                                            <svg class="icon icon-filter" aria-hidden="true" focusable="false"
                                                xmlns="http://www.w3.org/2000/svg" viewbox="0 0 20 20" fill="none">
                                                <path fill-rule="evenodd"
                                                    d="M4.833 6.5a1.667 1.667 0 1 1 3.334 0 1.667 1.667 0 0 1-3.334 0ZM4.05 7H2.5a.5.5 0 0 1 0-1h1.55a2.5 2.5 0 0 1 4.9 0h8.55a.5.5 0 0 1 0 1H8.95a2.5 2.5 0 0 1-4.9 0Zm11.117 6.5a1.667 1.667 0 1 0-3.334 0 1.667 1.667 0 0 0 3.334 0ZM13.5 11a2.5 2.5 0 0 1 2.45 2h1.55a.5.5 0 0 1 0 1h-1.55a2.5 2.5 0 0 1-4.9 0H2.5a.5.5 0 0 1 0-1h8.55a2.5 2.5 0 0 1 2.45-2Z"
                                                    fill="currentColor"></path>
                                            </svg>

                                            <span
                                                class="mobile-facets__open-label button-label medium-hide large-up-hide">Filter
                                                and sort
                                            </span>
                                            <span class="mobile-facets__open-label button-label small-hide">Filter
                                            </span>
                                        </span>
                                        <span tabindex="0" class="mobile-facets__close mobile-facets__close--no-js"><svg
                                                xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"
                                                class="icon icon-close" fill="none" viewbox="0 0 18 17">
                                                <path
                                                    d="M.865 15.978a.5.5 0 00.707.707l7.433-7.431 7.579 7.282a.501.501 0 00.846-.37.5.5 0 00-.153-.351L9.712 8.546l7.417-7.416a.5.5 0 10-.707-.708L8.991 7.853 1.413.573a.5.5 0 10-.693.72l7.563 7.268-7.418 7.417z"
                                                    fill="currentColor"></path>
                                            </svg>
                                        </span>
                                    </summary>
                                    <facet-filters-form>
                                        <form id="FacetFiltersFormMobile" class="mobile-facets">
                                            <div class="mobile-facets__inner gradient">
                                                <div class="mobile-facets__header">
                                                    <div class="mobile-facets__header-inner">
                                                        <h2 class="mobile-facets__heading medium-hide large-up-hide">
                                                            Filter and sort
                                                        </h2>
                                                        <h2 class="mobile-facets__heading small-hide">
                                                            Filter
                                                        </h2>
                                                        <p class="mobile-facets__count">6714 products</p>
                                                    </div>
                                                </div>
                                                <div class="mobile-facets__main has-submenu gradient">
                                                    <div class="mobile-facets__details js-filter" data-index="mobile-">
                                                        <div class="mobile-facets__summary">
                                                            <div class="mobile-facets__sort">
                                                                <label for="SortBy-mobile">Sort by:</label>
                                                                <div class="select">
                                                                    <select name="sort_by" class="select__select"
                                                                        id="SortBy-mobile"
                                                                        aria-describedby="a11y-refresh-page-message">
                                                                        <option value="manual">Featured</option>
                                                                        <option value="best-selling">
                                                                            Best selling
                                                                        </option>
                                                                        <option value="title-ascending" selected>
                                                                            Alphabetically, A-Z
                                                                        </option>
                                                                        <option value="title-descending">
                                                                            Alphabetically, Z-A
                                                                        </option>
                                                                        <option value="price-ascending">
                                                                            Price, low to high
                                                                        </option>
                                                                        <option value="price-descending">
                                                                            Price, high to low
                                                                        </option>
                                                                        <option value="created-ascending">
                                                                            Date, old to new
                                                                        </option>
                                                                        <option value="created-descending">
                                                                            Date, new to old
                                                                        </option>
                                                                    </select>
                                                                    <svg aria-hidden="true" focusable="false"
                                                                        class="icon icon-caret" viewbox="0 0 10 6">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M9.354.646a.5.5 0 00-.708 0L5 4.293 1.354.646a.5.5 0 00-.708.708l4 4a.5.5 0 00.708 0l4-4a.5.5 0 000-.708z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mobile-facets__footer">
                                                        <facet-remove class="mobile-facets__clear-wrapper">
                                                            <a href="all.html"
                                                                class="mobile-facets__clear underlined-link">Remove all</a>
                                                        </facet-remove>
                                                        <button type="button" class="no-js-hidden button button--primary"
                                                            onclick="this.closest('.mobile-facets__wrapper').querySelector('summary').click()">
                                                            Apply
                                                        </button>
                                                        <noscript><button class="button button--primary">
                                                                Apply
                                                            </button></noscript>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </facet-filters-form>
                                </details>
                            </menu-drawer>

                            <div class="active-facets active-facets-mobile medium-hide large-up-hide">
                                <facet-remove class="active-facets__button-wrapper">
                                    <a href="all.html" class="active-facets__button-remove underlined-link">
                                        <span>Remove all</span>
                                    </a>
                                </facet-remove>
                            </div>

                            <div class="product-count light medium-hide large-up-hide" role="status">
                                <h2 class="product-count__text text-body">
                                    <span id="ProductCount">6714 products </span>
                                </h2>
                                <div class="loading-overlay__spinner">
                                    <svg aria-hidden="true" focusable="false" class="spinner" viewbox="0 0 66 66"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <circle class="path" fill="none" stroke-width="6" cx="33"
                                            cy="33" r="30"></circle>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </aside>
                    <div class="product-grid-container" id="ProductGridContainer">
                        <div class="collection page-width">
                            <div class="loading-overlay gradient"></div>

                            <ul id="product-grid" data-id="template--16988185755808__product-grid"
                                class="grid product-grid grid--2-col-tablet-down grid--4-col-desktop">
                                <li class="grid__item">
                                    <div class="card-wrapper product-card-wrapper underline-links-hover">
                                        <div class="card card--standard card--media" style="--ratio-percent: 100%">
                                            <div class="card__inner color-background-2 gradient ratio"
                                                style="--ratio-percent: 100%">
                                                <div class="card__media">
                                                    <div class="media media--transparent media--hover-effect">
                                                        <img src="{{asset('asset/img/frontend/products-Asset30_4x_4f05e0de-c085-4a2b-994e-88917033bc1f.png')}}"
                                                            alt="AES Class 1 Summer Boys Dress Pant ~ 3 - 2071"
                                                            class="motion-reduce" width="162" height="401" />
                                                    </div>
                                                </div>
                                                <div class="card__content">
                                                    <div class="card__information">
                                                        <h3 class="card__heading">
                                                            <a href="aes-class-1-summer-boys-dress-pant-3-2071.html"
                                                                id="StandardCardNoMediaLink-template--16988185755808__product-grid-7039416565920"
                                                                class="full-unstyled-link"
                                                                aria-labelledby="StandardCardNoMediaLink-template--16988185755808__product-grid-7039416565920 NoMediaStandardBadge-template--16988185755808__product-grid-7039416565920">
                                                                AES Class 1 Summer Boys Dress Pant
                                                            </a>
                                                        </h3>
                                                    </div>
                                                    <div class="card__badge bottom left"></div>
                                                </div>
                                            </div>
                                            <div class="card__content">
                                                <div class="card__information">
                                                    <h3 class="card__heading h5"
                                                        id="title-template--16988185755808__product-grid-7039416565920">
                                                        <a href="aes-class-1-summer-boys-dress-pant-3-2071.html"
                                                            id="CardLink-template--16988185755808__product-grid-7039416565920"
                                                            class="full-unstyled-link"
                                                            aria-labelledby="CardLink-template--16988185755808__product-grid-7039416565920 Badge-template--16988185755808__product-grid-7039416565920">
                                                            AES Class 1 Summer Boys Dress Pant
                                                        </a>
                                                    </h3>
                                                    <div class="card-information">
                                                        <span class="caption-large light"></span>
                                                        <div class="price">
                                                            <div class="price__container">
                                                                <div class="price__regular">
                                                                    <span
                                                                        class="visually-hidden visually-hidden--inline">Regular
                                                                        price</span>
                                                                    <span class="price-item price-item--regular">
                                                                        From Rs.1,235.00
                                                                    </span>
                                                                </div>
                                                                <div class="price__sale">
                                                                    <span
                                                                        class="visually-hidden visually-hidden--inline">Regular
                                                                        price</span>
                                                                    <span>
                                                                        <s class="price-item price-item--regular">
                                                                        </s> </span><span
                                                                        class="visually-hidden visually-hidden--inline">Sale
                                                                        price</span>
                                                                    <span
                                                                        class="price-item price-item--sale price-item--last">
                                                                        From Rs.1,235.00
                                                                    </span>
                                                                </div>
                                                                <small class="unit-price caption hidden">
                                                                    <span class="visually-hidden">Unit price</span>
                                                                    <span class="price-item price-item--last">
                                                                        <span></span>
                                                                        <span aria-hidden="true">/</span>
                                                                        <span
                                                                            class="visually-hidden">&nbsp;per&nbsp;</span>
                                                                        <span> </span>
                                                                    </span>
                                                                </small>
                                                            </div>
                                                        </div>

                                                        <variant-selects
                                                            id="variant-selects-template--16988185755808__product-grid"
                                                            class="no-js-hidden"
                                                            data-section="template--16988185755808__product-grid"
                                                            data-url="/products/aes-class-1-summer-boys-dress-pant-3-2071">
                                                            <div class="product-form__input product-form__input--dropdown">
                                                                <label class="form__label"
                                                                    for="Option-template--16988185755808__product-grid-0">
                                                                    Height
                                                                </label>
                                                                <div class="select">
                                                                    <select
                                                                        id="Option-template--16988185755808__product-grid-0"
                                                                        class="select__select" name="options[Height]"
                                                                        form="product-form-7039416565920">
                                                                        <option value="100cm" selected>
                                                                            100cm
                                                                        </option>
                                                                        <option value="110cm">110cm</option>
                                                                        <option value="120cm">120cm</option>
                                                                        <option value="130cm">130cm</option>
                                                                        <option value="140cm">140cm</option>
                                                                        <option value="150cm">150cm</option>
                                                                    </select>
                                                                    <svg aria-hidden="true" focusable="false"
                                                                        class="icon icon-caret" viewbox="0 0 10 6">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M9.354.646a.5.5 0 00-.708 0L5 4.293 1.354.646a.5.5 0 00-.708.708l4 4a.5.5 0 00.708 0l4-4a.5.5 0 000-.708z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>

                                                        </variant-selects><noscript
                                                            class="product-form__noscript-wrapper-template--16988185755808__product-grid">
                                                            <div class="product-form__input">
                                                                <label class="form__label"
                                                                    for="Variants-template--16988185755808__product-grid">Product
                                                                    variants</label>
                                                                <div class="select">
                                                                    <select name="id"
                                                                        id="Variants-template--16988185755808__product-grid"
                                                                        class="select__select"
                                                                        form="product-form-7039416565920">
                                                                        <option selected value="41091201302688">
                                                                            100cm / Summer / 1 - Rs.1,235.00
                                                                        </option>
                                                                        <option value="41091201335456">
                                                                            110cm / Summer / 1 - Rs.1,285.00
                                                                        </option>
                                                                        <option value="41091201368224">
                                                                            120cm / Summer / 1 - Rs.1,335.00
                                                                        </option>
                                                                        <option value="41091201400992">
                                                                            130cm / Summer / 1 - Rs.1,385.00
                                                                        </option>
                                                                        <option value="41091201433760">
                                                                            140cm / Summer / 1 - Rs.1,435.00
                                                                        </option>
                                                                        <option value="41091201466528">
                                                                            150cm / Summer / 1 - Rs.1,485.00
                                                                        </option>
                                                                    </select>
                                                                    <svg aria-hidden="true" focusable="false"
                                                                        class="icon icon-caret" viewbox="0 0 10 6">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M9.354.646a.5.5 0 00-.708 0L5 4.293 1.354.646a.5.5 0 00-.708.708l4 4a.5.5 0 00.708 0l4-4a.5.5 0 000-.708z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        </noscript>

                                                        <div id="Quantity-Form-7039416565920"
                                                            class="product-form__input product-form__quantity">
                                                            <label class="quantity__label form__label"
                                                                for="Quantity-template--16988185755808__product-grid">
                                                                Quantity
                                                                <span class="quantity__rules-cart no-js-hidden hidden">
                                                                    <span class="loading-overlay hidden">
                                                                        <span class="loading-overlay__spinner">
                                                                            <svg aria-hidden="true" focusable="false"
                                                                                class="spinner" viewbox="0 0 66 66"
                                                                                xmlns="http://www.w3.org/2000/svg">
                                                                                <circle class="path" fill="none"
                                                                                    stroke-width="6" cx="33"
                                                                                    cy="33" r="30"></circle>
                                                                            </svg>
                                                                        </span>
                                                                    </span>
                                                                    <span>(<span class="quantity-cart">0</span> in
                                                                        cart)</span>
                                                                </span>
                                                            </label>
                                                            <quantity-input class="quantity">
                                                                <button class="quantity__button no-js-hidden"
                                                                    name="minus" type="button">
                                                                    <span class="visually-hidden">Decrease quantity for AES
                                                                        Class 1 Summer
                                                                        Boys Dress Pant ~ 3 - 2071</span>
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        aria-hidden="true" focusable="false"
                                                                        class="icon icon-minus" fill="none"
                                                                        viewbox="0 0 10 2">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M.5 1C.5.7.7.5 1 .5h8a.5.5 0 110 1H1A.5.5 0 01.5 1z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </button>
                                                                <input class="quantity__input" type="number"
                                                                    name="quantity"
                                                                    id="Quantity-template--16988185755808__product-grid"
                                                                    data-cart-quantity="0" data-min="1" min="1"
                                                                    step="1" value="1"
                                                                    form="product-form-7039416565920" />
                                                                <button class="quantity__button no-js-hidden"
                                                                    name="plus" type="button">
                                                                    <span class="visually-hidden">Increase quantity for AES
                                                                        Class 1 Summer
                                                                        Boys Dress Pant ~ 3 - 2071</span>
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        aria-hidden="true" focusable="false"
                                                                        class="icon icon-plus" fill="none"
                                                                        viewbox="0 0 10 10">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M1 4.51a.5.5 0 000 1h3.5l.01 3.5a.5.5 0 001-.01V5.5l3.5-.01a.5.5 0 00-.01-1H5.5L5.49.99a.5.5 0 00-1 .01v3.5l-3.5.01H1z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </button>
                                                            </quantity-input>
                                                            <div class="quantity__rules caption no-js-hidden"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card__badge bottom left"></div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="grid__item">
                                    <div class="card-wrapper product-card-wrapper underline-links-hover">
                                        <div class="card card--standard card--media" style="--ratio-percent: 100%">
                                            <div class="card__inner color-background-2 gradient ratio"
                                                style="--ratio-percent: 100%">
                                                <div class="card__media">
                                                    <div class="media media--transparent media--hover-effect">
                                                        <img 
                                                            src="{{asset('asset/img/frontend/products-Asset1_4x_debfd0e5-99e9-4a3f-90cb-bd45cc1de92c.png')}}"
                                                            alt="AES Class 1 Summer Boys Formal Shirt ~ 1 - 1171"
                                                            class="motion-reduce" width="401" height="330" />
                                                    </div>
                                                </div>
                                                <div class="card__content">
                                                    <div class="card__information">
                                                        <h3 class="card__heading">
                                                            <a href="aes-class-1-summer-boys-formal-shirt-1-1171.html"
                                                                id="StandardCardNoMediaLink-template--16988185755808__product-grid-7039416533152"
                                                                class="full-unstyled-link"
                                                                aria-labelledby="StandardCardNoMediaLink-template--16988185755808__product-grid-7039416533152 NoMediaStandardBadge-template--16988185755808__product-grid-7039416533152">
                                                                AES Class 1 Summer Boys Formal Shirt
                                                            </a>
                                                        </h3>
                                                    </div>
                                                    <div class="card__badge bottom left"></div>
                                                </div>
                                            </div>
                                            <div class="card__content">
                                                <div class="card__information">
                                                    <h3 class="card__heading h5"
                                                        id="title-template--16988185755808__product-grid-7039416533152">
                                                        <a href="aes-class-1-summer-boys-formal-shirt-1-1171.html"
                                                            id="CardLink-template--16988185755808__product-grid-7039416533152"
                                                            class="full-unstyled-link"
                                                            aria-labelledby="CardLink-template--16988185755808__product-grid-7039416533152 Badge-template--16988185755808__product-grid-7039416533152">
                                                            AES Class 1 Summer Boys Formal Shirt
                                                        </a>
                                                    </h3>
                                                    <div class="card-information">
                                                        <span class="caption-large light"></span>
                                                        <div class="price">
                                                            <div class="price__container">
                                                                <div class="price__regular">
                                                                    <span
                                                                        class="visually-hidden visually-hidden--inline">Regular
                                                                        price</span>
                                                                    <span class="price-item price-item--regular">
                                                                        From Rs.1,070.00
                                                                    </span>
                                                                </div>
                                                                <div class="price__sale">
                                                                    <span
                                                                        class="visually-hidden visually-hidden--inline">Regular
                                                                        price</span>
                                                                    <span>
                                                                        <s class="price-item price-item--regular">
                                                                        </s> </span><span
                                                                        class="visually-hidden visually-hidden--inline">Sale
                                                                        price</span>
                                                                    <span
                                                                        class="price-item price-item--sale price-item--last">
                                                                        From Rs.1,070.00
                                                                    </span>
                                                                </div>
                                                                <small class="unit-price caption hidden">
                                                                    <span class="visually-hidden">Unit price</span>
                                                                    <span class="price-item price-item--last">
                                                                        <span></span>
                                                                        <span aria-hidden="true">/</span>
                                                                        <span
                                                                            class="visually-hidden">&nbsp;per&nbsp;</span>
                                                                        <span> </span>
                                                                    </span>
                                                                </small>
                                                            </div>
                                                        </div>

                                                        <variant-selects
                                                            id="variant-selects-template--16988185755808__product-grid"
                                                            class="no-js-hidden"
                                                            data-section="template--16988185755808__product-grid"
                                                            data-url="/products/aes-class-1-summer-boys-formal-shirt-1-1171">
                                                            <div class="product-form__input product-form__input--dropdown">
                                                                <label class="form__label"
                                                                    for="Option-template--16988185755808__product-grid-0">
                                                                    Height
                                                                </label>
                                                                <div class="select">
                                                                    <select
                                                                        id="Option-template--16988185755808__product-grid-0"
                                                                        class="select__select" name="options[Height]"
                                                                        form="product-form-7039416533152">
                                                                        <option value="100cm" selected>
                                                                            100cm
                                                                        </option>
                                                                        <option value="110cm">110cm</option>
                                                                        <option value="120cm">120cm</option>
                                                                        <option value="130cm">130cm</option>
                                                                        <option value="140cm">140cm</option>
                                                                        <option value="150cm">150cm</option>
                                                                        <option value="160cm">160cm</option>
                                                                        <option value="170cm">170cm</option>
                                                                        <option value="180cm">180cm</option>
                                                                        <option value="190cm">190cm</option>
                                                                    </select>
                                                                    <svg aria-hidden="true" focusable="false"
                                                                        class="icon icon-caret" viewbox="0 0 10 6">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M9.354.646a.5.5 0 00-.708 0L5 4.293 1.354.646a.5.5 0 00-.708.708l4 4a.5.5 0 00.708 0l4-4a.5.5 0 000-.708z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>

                                                        </variant-selects><noscript
                                                            class="product-form__noscript-wrapper-template--16988185755808__product-grid">
                                                            <div class="product-form__input">
                                                                <label class="form__label"
                                                                    for="Variants-template--16988185755808__product-grid">Product
                                                                    variants</label>
                                                                <div class="select">
                                                                    <select name="id"
                                                                        id="Variants-template--16988185755808__product-grid"
                                                                        class="select__select"
                                                                        form="product-form-7039416533152">
                                                                        <option selected value="41091200778400">
                                                                            100cm / Summer / 1 - Rs.1,070.00
                                                                        </option>
                                                                        <option value="41091200843936">
                                                                            110cm / Summer / 1 - Rs.1,120.00
                                                                        </option>
                                                                        <option value="41091200909472">
                                                                            120cm / Summer / 1 - Rs.1,170.00
                                                                        </option>
                                                                        <option value="41091200975008">
                                                                            130cm / Summer / 1 - Rs.1,220.00
                                                                        </option>
                                                                        <option value="41091201040544">
                                                                            140cm / Summer / 1 - Rs.1,270.00
                                                                        </option>
                                                                        <option value="41091201073312">
                                                                            150cm / Summer / 1 - Rs.1,320.00
                                                                        </option>
                                                                        <option value="41091201138848">
                                                                            160cm / Summer / 1 - Rs.1,370.00
                                                                        </option>
                                                                        <option value="41091201171616">
                                                                            170cm / Summer / 1 - Rs.1,420.00
                                                                        </option>
                                                                        <option value="41091201204384">
                                                                            180cm / Summer / 1 - Rs.1,470.00
                                                                        </option>
                                                                        <option value="41091201237152">
                                                                            190cm / Summer / 1 - Rs.1,520.00
                                                                        </option>
                                                                    </select>
                                                                    <svg aria-hidden="true" focusable="false"
                                                                        class="icon icon-caret" viewbox="0 0 10 6">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M9.354.646a.5.5 0 00-.708 0L5 4.293 1.354.646a.5.5 0 00-.708.708l4 4a.5.5 0 00.708 0l4-4a.5.5 0 000-.708z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        </noscript>

                                                        <div id="Quantity-Form-7039416533152"
                                                            class="product-form__input product-form__quantity">
                                                            <label class="quantity__label form__label"
                                                                for="Quantity-template--16988185755808__product-grid">
                                                                Quantity
                                                                <span class="quantity__rules-cart no-js-hidden hidden">
                                                                    <span class="loading-overlay hidden">
                                                                        <span class="loading-overlay__spinner">
                                                                            <svg aria-hidden="true" focusable="false"
                                                                                class="spinner" viewbox="0 0 66 66"
                                                                                xmlns="http://www.w3.org/2000/svg">
                                                                                <circle class="path" fill="none"
                                                                                    stroke-width="6" cx="33"
                                                                                    cy="33" r="30"></circle>
                                                                            </svg>
                                                                        </span>
                                                                    </span>
                                                                    <span>(<span class="quantity-cart">0</span> in
                                                                        cart)</span>
                                                                </span>
                                                            </label>
                                                            <quantity-input class="quantity">
                                                                <button class="quantity__button no-js-hidden"
                                                                    name="minus" type="button">
                                                                    <span class="visually-hidden">Decrease quantity for AES
                                                                        Class 1 Summer
                                                                        Boys Formal Shirt ~ 1 - 1171</span>
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        aria-hidden="true" focusable="false"
                                                                        class="icon icon-minus" fill="none"
                                                                        viewbox="0 0 10 2">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M.5 1C.5.7.7.5 1 .5h8a.5.5 0 110 1H1A.5.5 0 01.5 1z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </button>
                                                                <input class="quantity__input" type="number"
                                                                    name="quantity"
                                                                    id="Quantity-template--16988185755808__product-grid"
                                                                    data-cart-quantity="0" data-min="1" min="1"
                                                                    step="1" value="1"
                                                                    form="product-form-7039416533152" />
                                                                <button class="quantity__button no-js-hidden"
                                                                    name="plus" type="button">
                                                                    <span class="visually-hidden">Increase quantity for AES
                                                                        Class 1 Summer
                                                                        Boys Formal Shirt ~ 1 - 1171</span>
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        aria-hidden="true" focusable="false"
                                                                        class="icon icon-plus" fill="none"
                                                                        viewbox="0 0 10 10">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M1 4.51a.5.5 0 000 1h3.5l.01 3.5a.5.5 0 001-.01V5.5l3.5-.01a.5.5 0 00-.01-1H5.5L5.49.99a.5.5 0 00-1 .01v3.5l-3.5.01H1z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </button>
                                                            </quantity-input>
                                                            <div class="quantity__rules caption no-js-hidden"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card__badge bottom left"></div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="grid__item">
                                    <div class="card-wrapper product-card-wrapper underline-links-hover">
                                        <div class="card card--standard card--media" style="--ratio-percent: 100%">
                                            <div class="card__inner color-background-2 gradient ratio"
                                                style="--ratio-percent: 100%">
                                                <div class="card__media">
                                                    <div class="media media--transparent media--hover-effect">
                                                        <img 
                                                            src="{{asset('asset/img/frontend/products-School_Belt_cada18de-99ed-4726-91d4-478bfc0d5ae0.png')}}"
                                                            
                                                            alt="AES Class 1 Summer Boys School Belt ~ 45 - 2307"
                                                            class="motion-reduce" loading="lazy" width="100"
                                                            height="100" />
                                                    </div>
                                                </div>
                                                <div class="card__content">
                                                    <div class="card__information">
                                                        <h3 class="card__heading">
                                                            <a href="aes-class-1-summer-boys-school-belt-45-2307.html"
                                                                id="StandardCardNoMediaLink-template--16988185755808__product-grid-7039416664224"
                                                                class="full-unstyled-link"
                                                                aria-labelledby="StandardCardNoMediaLink-template--16988185755808__product-grid-7039416664224 NoMediaStandardBadge-template--16988185755808__product-grid-7039416664224">
                                                                AES Class 1 Summer Boys School Belt
                                                            </a>
                                                        </h3>
                                                    </div>
                                                    <div class="card__badge bottom left"></div>
                                                </div>
                                            </div>
                                            <div class="card__content">
                                                <div class="card__information">
                                                    <h3 class="card__heading h5"
                                                        id="title-template--16988185755808__product-grid-7039416664224">
                                                        <a href="aes-class-1-summer-boys-school-belt-45-2307.html"
                                                            id="CardLink-template--16988185755808__product-grid-7039416664224"
                                                            class="full-unstyled-link"
                                                            aria-labelledby="CardLink-template--16988185755808__product-grid-7039416664224 Badge-template--16988185755808__product-grid-7039416664224">
                                                            AES Class 1 Summer Boys School Belt
                                                        </a>
                                                    </h3>
                                                    <div class="card-information">
                                                        <span class="caption-large light"></span>
                                                        <div class="price">
                                                            <div class="price__container">
                                                                <div class="price__regular">
                                                                    <span
                                                                        class="visually-hidden visually-hidden--inline">Regular
                                                                        price</span>
                                                                    <span class="price-item price-item--regular">
                                                                        Rs.255.00
                                                                    </span>
                                                                </div>
                                                                <div class="price__sale">
                                                                    <span
                                                                        class="visually-hidden visually-hidden--inline">Regular
                                                                        price</span>
                                                                    <span>
                                                                        <s class="price-item price-item--regular">
                                                                        </s> </span><span
                                                                        class="visually-hidden visually-hidden--inline">Sale
                                                                        price</span>
                                                                    <span
                                                                        class="price-item price-item--sale price-item--last">
                                                                        Rs.255.00
                                                                    </span>
                                                                </div>
                                                                <small class="unit-price caption hidden">
                                                                    <span class="visually-hidden">Unit price</span>
                                                                    <span class="price-item price-item--last">
                                                                        <span></span>
                                                                        <span aria-hidden="true">/</span>
                                                                        <span
                                                                            class="visually-hidden">&nbsp;per&nbsp;</span>
                                                                        <span> </span>
                                                                    </span>
                                                                </small>
                                                            </div>
                                                        </div>

                                                        <variant-selects
                                                            id="variant-selects-template--16988185755808__product-grid"
                                                            class="no-js-hidden"
                                                            data-section="template--16988185755808__product-grid"
                                                            data-url="/products/aes-class-1-summer-boys-school-belt-45-2307">
                                                            <div class="product-form__input product-form__input--dropdown">
                                                                <label class="form__label"
                                                                    for="Option-template--16988185755808__product-grid-0">
                                                                    Height
                                                                </label>
                                                                <div class="select">
                                                                    <select
                                                                        id="Option-template--16988185755808__product-grid-0"
                                                                        class="select__select" name="options[Height]"
                                                                        form="product-form-7039416664224">
                                                                        <option value="100cm" selected>
                                                                            100cm
                                                                        </option>
                                                                        <option value="110cm">110cm</option>
                                                                        <option value="120cm">120cm</option>
                                                                    </select>
                                                                    <svg aria-hidden="true" focusable="false"
                                                                        class="icon icon-caret" viewbox="0 0 10 6">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M9.354.646a.5.5 0 00-.708 0L5 4.293 1.354.646a.5.5 0 00-.708.708l4 4a.5.5 0 00.708 0l4-4a.5.5 0 000-.708z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>

                                                        </variant-selects><noscript
                                                            class="product-form__noscript-wrapper-template--16988185755808__product-grid">
                                                            <div class="product-form__input">
                                                                <label class="form__label"
                                                                    for="Variants-template--16988185755808__product-grid">Product
                                                                    variants</label>
                                                                <div class="select">
                                                                    <select name="id"
                                                                        id="Variants-template--16988185755808__product-grid"
                                                                        class="select__select"
                                                                        form="product-form-7039416664224">
                                                                        <option selected value="41091201695904">
                                                                            100cm / Summer / 1 - Rs.255.00
                                                                        </option>
                                                                        <option value="41091201728672">
                                                                            110cm / Summer / 1 - Rs.255.00
                                                                        </option>
                                                                        <option value="41091201761440">
                                                                            120cm / Summer / 1 - Rs.255.00
                                                                        </option>
                                                                    </select>
                                                                    <svg aria-hidden="true" focusable="false"
                                                                        class="icon icon-caret" viewbox="0 0 10 6">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M9.354.646a.5.5 0 00-.708 0L5 4.293 1.354.646a.5.5 0 00-.708.708l4 4a.5.5 0 00.708 0l4-4a.5.5 0 000-.708z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        </noscript>

                                                        <div id="Quantity-Form-7039416664224"
                                                            class="product-form__input product-form__quantity">
                                                            <label class="quantity__label form__label"
                                                                for="Quantity-template--16988185755808__product-grid">
                                                                Quantity
                                                                <span class="quantity__rules-cart no-js-hidden hidden">
                                                                    <span class="loading-overlay hidden">
                                                                        <span class="loading-overlay__spinner">
                                                                            <svg aria-hidden="true" focusable="false"
                                                                                class="spinner" viewbox="0 0 66 66"
                                                                                xmlns="http://www.w3.org/2000/svg">
                                                                                <circle class="path" fill="none"
                                                                                    stroke-width="6" cx="33"
                                                                                    cy="33" r="30"></circle>
                                                                            </svg>
                                                                        </span>
                                                                    </span>
                                                                    <span>(<span class="quantity-cart">0</span> in
                                                                        cart)</span>
                                                                </span>
                                                            </label>
                                                            <quantity-input class="quantity">
                                                                <button class="quantity__button no-js-hidden"
                                                                    name="minus" type="button">
                                                                    <span class="visually-hidden">Decrease quantity for AES
                                                                        Class 1 Summer
                                                                        Boys School Belt ~ 45 - 2307</span>
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        aria-hidden="true" focusable="false"
                                                                        class="icon icon-minus" fill="none"
                                                                        viewbox="0 0 10 2">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M.5 1C.5.7.7.5 1 .5h8a.5.5 0 110 1H1A.5.5 0 01.5 1z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </button>
                                                                <input class="quantity__input" type="number"
                                                                    name="quantity"
                                                                    id="Quantity-template--16988185755808__product-grid"
                                                                    data-cart-quantity="0" data-min="1" min="1"
                                                                    step="1" value="1"
                                                                    form="product-form-7039416664224" />
                                                                <button class="quantity__button no-js-hidden"
                                                                    name="plus" type="button">
                                                                    <span class="visually-hidden">Increase quantity for AES
                                                                        Class 1 Summer
                                                                        Boys School Belt ~ 45 - 2307</span>
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        aria-hidden="true" focusable="false"
                                                                        class="icon icon-plus" fill="none"
                                                                        viewbox="0 0 10 10">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M1 4.51a.5.5 0 000 1h3.5l.01 3.5a.5.5 0 001-.01V5.5l3.5-.01a.5.5 0 00-.01-1H5.5L5.49.99a.5.5 0 00-1 .01v3.5l-3.5.01H1z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </button>
                                                            </quantity-input>
                                                            <div class="quantity__rules caption no-js-hidden"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card__badge bottom left"></div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="grid__item">
                                    <div class="card-wrapper product-card-wrapper underline-links-hover">
                                        <div class="card card--standard card--media" style="--ratio-percent: 100%">
                                            <div class="card__inner color-background-2 gradient ratio"
                                                style="--ratio-percent: 100%">
                                                <div class="card__media">
                                                    <div class="media media--transparent media--hover-effect">
                                                        <img 
                                                            src="{{asset('asset/img/frontend/products-Asset16_4x_4083cf47-c012-46ba-b7b8-fc47177ba8e4.png')}}"
                                                            
                                                            alt="AES Class 1 Summer Boys Socks ~ 46 - 5001"
                                                            class="motion-reduce" loading="lazy" width="265"
                                                            height="270" />
                                                    </div>
                                                </div>
                                                <div class="card__content">
                                                    <div class="card__information">
                                                        <h3 class="card__heading">
                                                            <a href="aes-class-1-summer-boys-socks-46-5001.html"
                                                                id="StandardCardNoMediaLink-template--16988185755808__product-grid-7039416598688"
                                                                class="full-unstyled-link"
                                                                aria-labelledby="StandardCardNoMediaLink-template--16988185755808__product-grid-7039416598688 NoMediaStandardBadge-template--16988185755808__product-grid-7039416598688">
                                                                AES Class 1 Summer Boys Socks
                                                            </a>
                                                        </h3>
                                                    </div>
                                                    <div class="card__badge bottom left"></div>
                                                </div>
                                            </div>
                                            <div class="card__content">
                                                <div class="card__information">
                                                    <h3 class="card__heading h5"
                                                        id="title-template--16988185755808__product-grid-7039416598688">
                                                        <a href="aes-class-1-summer-boys-socks-46-5001.html"
                                                            id="CardLink-template--16988185755808__product-grid-7039416598688"
                                                            class="full-unstyled-link"
                                                            aria-labelledby="CardLink-template--16988185755808__product-grid-7039416598688 Badge-template--16988185755808__product-grid-7039416598688">
                                                            AES Class 1 Summer Boys Socks
                                                        </a>
                                                    </h3>
                                                    <div class="card-information">
                                                        <span class="caption-large light"></span>
                                                        <div class="price">
                                                            <div class="price__container">
                                                                <div class="price__regular">
                                                                    <span
                                                                        class="visually-hidden visually-hidden--inline">Regular
                                                                        price</span>
                                                                    <span class="price-item price-item--regular">
                                                                        From Rs.195.00
                                                                    </span>
                                                                </div>
                                                                <div class="price__sale">
                                                                    <span
                                                                        class="visually-hidden visually-hidden--inline">Regular
                                                                        price</span>
                                                                    <span>
                                                                        <s class="price-item price-item--regular">
                                                                        </s> </span><span
                                                                        class="visually-hidden visually-hidden--inline">Sale
                                                                        price</span>
                                                                    <span
                                                                        class="price-item price-item--sale price-item--last">
                                                                        From Rs.195.00
                                                                    </span>
                                                                </div>
                                                                <small class="unit-price caption hidden">
                                                                    <span class="visually-hidden">Unit price</span>
                                                                    <span class="price-item price-item--last">
                                                                        <span></span>
                                                                        <span aria-hidden="true">/</span>
                                                                        <span
                                                                            class="visually-hidden">&nbsp;per&nbsp;</span>
                                                                        <span> </span>
                                                                    </span>
                                                                </small>
                                                            </div>
                                                        </div>

                                                        <variant-selects
                                                            id="variant-selects-template--16988185755808__product-grid"
                                                            class="no-js-hidden"
                                                            data-section="template--16988185755808__product-grid"
                                                            data-url="/products/aes-class-1-summer-boys-socks-46-5001">
                                                            <div class="product-form__input product-form__input--dropdown">
                                                                <label class="form__label"
                                                                    for="Option-template--16988185755808__product-grid-0">
                                                                    Height
                                                                </label>
                                                                <div class="select">
                                                                    <select
                                                                        id="Option-template--16988185755808__product-grid-0"
                                                                        class="select__select" name="options[Height]"
                                                                        form="product-form-7039416598688">
                                                                        <option value="100cm" selected>
                                                                            100cm
                                                                        </option>
                                                                        <option value="110cm">110cm</option>
                                                                        <option value="120cm">120cm</option>
                                                                        <option value="130cm">130cm</option>
                                                                        <option value="140cm">140cm</option>
                                                                    </select>
                                                                    <svg aria-hidden="true" focusable="false"
                                                                        class="icon icon-caret" viewbox="0 0 10 6">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M9.354.646a.5.5 0 00-.708 0L5 4.293 1.354.646a.5.5 0 00-.708.708l4 4a.5.5 0 00.708 0l4-4a.5.5 0 000-.708z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>

                                                        </variant-selects><noscript
                                                            class="product-form__noscript-wrapper-template--16988185755808__product-grid">
                                                            <div class="product-form__input">
                                                                <label class="form__label"
                                                                    for="Variants-template--16988185755808__product-grid">Product
                                                                    variants</label>
                                                                <div class="select">
                                                                    <select name="id"
                                                                        id="Variants-template--16988185755808__product-grid"
                                                                        class="select__select"
                                                                        form="product-form-7039416598688">
                                                                        <option selected value="41091201499296">
                                                                            100cm / Summer / 1 - Rs.195.00
                                                                        </option>
                                                                        <option value="41091201532064">
                                                                            110cm / Summer / 1 - Rs.205.00
                                                                        </option>
                                                                        <option value="41091201564832">
                                                                            120cm / Summer / 1 - Rs.215.00
                                                                        </option>
                                                                        <option value="41091201597600">
                                                                            130cm / Summer / 1 - Rs.225.00
                                                                        </option>
                                                                        <option value="41091201630368">
                                                                            140cm / Summer / 1 - Rs.235.00
                                                                        </option>
                                                                    </select>
                                                                    <svg aria-hidden="true" focusable="false"
                                                                        class="icon icon-caret" viewbox="0 0 10 6">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M9.354.646a.5.5 0 00-.708 0L5 4.293 1.354.646a.5.5 0 00-.708.708l4 4a.5.5 0 00.708 0l4-4a.5.5 0 000-.708z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        </noscript>

                                                        <div id="Quantity-Form-7039416598688"
                                                            class="product-form__input product-form__quantity">
                                                            <label class="quantity__label form__label"
                                                                for="Quantity-template--16988185755808__product-grid">
                                                                Quantity
                                                                <span class="quantity__rules-cart no-js-hidden hidden">
                                                                    <span class="loading-overlay hidden">
                                                                        <span class="loading-overlay__spinner">
                                                                            <svg aria-hidden="true" focusable="false"
                                                                                class="spinner" viewbox="0 0 66 66"
                                                                                xmlns="http://www.w3.org/2000/svg">
                                                                                <circle class="path" fill="none"
                                                                                    stroke-width="6" cx="33"
                                                                                    cy="33" r="30"></circle>
                                                                            </svg>
                                                                        </span>
                                                                    </span>
                                                                    <span>(<span class="quantity-cart">0</span> in
                                                                        cart)</span>
                                                                </span>
                                                            </label>
                                                            <quantity-input class="quantity">
                                                                <button class="quantity__button no-js-hidden"
                                                                    name="minus" type="button">
                                                                    <span class="visually-hidden">Decrease quantity for AES
                                                                        Class 1 Summer
                                                                        Boys Socks ~ 46 - 5001</span>
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        aria-hidden="true" focusable="false"
                                                                        class="icon icon-minus" fill="none"
                                                                        viewbox="0 0 10 2">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M.5 1C.5.7.7.5 1 .5h8a.5.5 0 110 1H1A.5.5 0 01.5 1z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </button>
                                                                <input class="quantity__input" type="number"
                                                                    name="quantity"
                                                                    id="Quantity-template--16988185755808__product-grid"
                                                                    data-cart-quantity="0" data-min="1" min="1"
                                                                    step="1" value="1"
                                                                    form="product-form-7039416598688" />
                                                                <button class="quantity__button no-js-hidden"
                                                                    name="plus" type="button">
                                                                    <span class="visually-hidden">Increase quantity for AES
                                                                        Class 1 Summer
                                                                        Boys Socks ~ 46 - 5001</span>
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        aria-hidden="true" focusable="false"
                                                                        class="icon icon-plus" fill="none"
                                                                        viewbox="0 0 10 10">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M1 4.51a.5.5 0 000 1h3.5l.01 3.5a.5.5 0 001-.01V5.5l3.5-.01a.5.5 0 00-.01-1H5.5L5.49.99a.5.5 0 00-1 .01v3.5l-3.5.01H1z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </button>
                                                            </quantity-input>
                                                            <div class="quantity__rules caption no-js-hidden"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card__badge bottom left"></div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="grid__item">
                                    <div class="card-wrapper product-card-wrapper underline-links-hover">
                                        <div class="card card--standard card--media" style="--ratio-percent: 100%">
                                            <div class="card__inner color-background-2 gradient ratio"
                                                style="--ratio-percent: 100%">
                                                <div class="card__media">
                                                    <div class="media media--transparent media--hover-effect">
                                                        <img 
                                                            src="{{asset('asset/img/frontend/products-rqiiUj_4x_b81e162f-8166-4767-8c4a-ede782ebbe1d.png')}}"
                                                            
                                                            alt="AES Class 1 Summer Boys Tie ~ 43 - 7571"
                                                            class="motion-reduce" loading="lazy" width="121"
                                                            height="375" />
                                                    </div>
                                                </div>
                                                <div class="card__content">
                                                    <div class="card__information">
                                                        <h3 class="card__heading">
                                                            <a href="aes-class-1-summer-boys-tie-43-7571.html"
                                                                id="StandardCardNoMediaLink-template--16988185755808__product-grid-7039416696992"
                                                                class="full-unstyled-link"
                                                                aria-labelledby="StandardCardNoMediaLink-template--16988185755808__product-grid-7039416696992 NoMediaStandardBadge-template--16988185755808__product-grid-7039416696992">
                                                                AES Class 1 Summer Boys Tie
                                                            </a>
                                                        </h3>
                                                    </div>
                                                    <div class="card__badge bottom left"></div>
                                                </div>
                                            </div>
                                            <div class="card__content">
                                                <div class="card__information">
                                                    <h3 class="card__heading h5"
                                                        id="title-template--16988185755808__product-grid-7039416696992">
                                                        <a href="aes-class-1-summer-boys-tie-43-7571.html"
                                                            id="CardLink-template--16988185755808__product-grid-7039416696992"
                                                            class="full-unstyled-link"
                                                            aria-labelledby="CardLink-template--16988185755808__product-grid-7039416696992 Badge-template--16988185755808__product-grid-7039416696992">
                                                            AES Class 1 Summer Boys Tie
                                                        </a>
                                                    </h3>
                                                    <div class="card-information">
                                                        <span class="caption-large light"></span>
                                                        <div class="price">
                                                            <div class="price__container">
                                                                <div class="price__regular">
                                                                    <span
                                                                        class="visually-hidden visually-hidden--inline">Regular
                                                                        price</span>
                                                                    <span class="price-item price-item--regular">
                                                                        From Rs.230.00
                                                                    </span>
                                                                </div>
                                                                <div class="price__sale">
                                                                    <span
                                                                        class="visually-hidden visually-hidden--inline">Regular
                                                                        price</span>
                                                                    <span>
                                                                        <s class="price-item price-item--regular">
                                                                        </s> </span><span
                                                                        class="visually-hidden visually-hidden--inline">Sale
                                                                        price</span>
                                                                    <span
                                                                        class="price-item price-item--sale price-item--last">
                                                                        From Rs.230.00
                                                                    </span>
                                                                </div>
                                                                <small class="unit-price caption hidden">
                                                                    <span class="visually-hidden">Unit price</span>
                                                                    <span class="price-item price-item--last">
                                                                        <span></span>
                                                                        <span aria-hidden="true">/</span>
                                                                        <span
                                                                            class="visually-hidden">&nbsp;per&nbsp;</span>
                                                                        <span> </span>
                                                                    </span>
                                                                </small>
                                                            </div>
                                                        </div>

                                                        <variant-selects
                                                            id="variant-selects-template--16988185755808__product-grid"
                                                            class="no-js-hidden"
                                                            data-section="template--16988185755808__product-grid"
                                                            data-url="/products/aes-class-1-summer-boys-tie-43-7571">
                                                            <div class="product-form__input product-form__input--dropdown">
                                                                <label class="form__label"
                                                                    for="Option-template--16988185755808__product-grid-0">
                                                                    Height
                                                                </label>
                                                                <div class="select">
                                                                    <select
                                                                        id="Option-template--16988185755808__product-grid-0"
                                                                        class="select__select" name="options[Height]"
                                                                        form="product-form-7039416696992">
                                                                        <option value="110cm" selected>
                                                                            110cm
                                                                        </option>
                                                                        <option value="120cm">120cm</option>
                                                                        <option value="130cm">130cm</option>
                                                                        <option value="140cm">140cm</option>
                                                                        <option value="150cm">150cm</option>
                                                                    </select>
                                                                    <svg aria-hidden="true" focusable="false"
                                                                        class="icon icon-caret" viewbox="0 0 10 6">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M9.354.646a.5.5 0 00-.708 0L5 4.293 1.354.646a.5.5 0 00-.708.708l4 4a.5.5 0 00.708 0l4-4a.5.5 0 000-.708z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>

                                                        </variant-selects><noscript
                                                            class="product-form__noscript-wrapper-template--16988185755808__product-grid">
                                                            <div class="product-form__input">
                                                                <label class="form__label"
                                                                    for="Variants-template--16988185755808__product-grid">Product
                                                                    variants</label>
                                                                <div class="select">
                                                                    <select name="id"
                                                                        id="Variants-template--16988185755808__product-grid"
                                                                        class="select__select"
                                                                        form="product-form-7039416696992">
                                                                        <option selected value="41091201794208">
                                                                            110cm / Summer / 1 - Rs.230.00
                                                                        </option>
                                                                        <option value="41091201826976">
                                                                            120cm / Summer / 1 - Rs.255.00
                                                                        </option>
                                                                        <option value="41091201859744">
                                                                            130cm / Summer / 1 - Rs.285.00
                                                                        </option>
                                                                        <option value="41091201892512">
                                                                            140cm / Summer / 1 - Rs.320.00
                                                                        </option>
                                                                        <option value="41091201925280">
                                                                            150cm / Summer / 1 - Rs.365.00
                                                                        </option>
                                                                    </select>
                                                                    <svg aria-hidden="true" focusable="false"
                                                                        class="icon icon-caret" viewbox="0 0 10 6">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M9.354.646a.5.5 0 00-.708 0L5 4.293 1.354.646a.5.5 0 00-.708.708l4 4a.5.5 0 00.708 0l4-4a.5.5 0 000-.708z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        </noscript>

                                                        <div id="Quantity-Form-7039416696992"
                                                            class="product-form__input product-form__quantity">
                                                            <label class="quantity__label form__label"
                                                                for="Quantity-template--16988185755808__product-grid">
                                                                Quantity
                                                                <span class="quantity__rules-cart no-js-hidden hidden">
                                                                    <span class="loading-overlay hidden">
                                                                        <span class="loading-overlay__spinner">
                                                                            <svg aria-hidden="true" focusable="false"
                                                                                class="spinner" viewbox="0 0 66 66"
                                                                                xmlns="http://www.w3.org/2000/svg">
                                                                                <circle class="path" fill="none"
                                                                                    stroke-width="6" cx="33"
                                                                                    cy="33" r="30"></circle>
                                                                            </svg>
                                                                        </span>
                                                                    </span>
                                                                    <span>(<span class="quantity-cart">0</span> in
                                                                        cart)</span>
                                                                </span>
                                                            </label>
                                                            <quantity-input class="quantity">
                                                                <button class="quantity__button no-js-hidden"
                                                                    name="minus" type="button">
                                                                    <span class="visually-hidden">Decrease quantity for AES
                                                                        Class 1 Summer
                                                                        Boys Tie ~ 43 - 7571</span>
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        aria-hidden="true" focusable="false"
                                                                        class="icon icon-minus" fill="none"
                                                                        viewbox="0 0 10 2">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M.5 1C.5.7.7.5 1 .5h8a.5.5 0 110 1H1A.5.5 0 01.5 1z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </button>
                                                                <input class="quantity__input" type="number"
                                                                    name="quantity"
                                                                    id="Quantity-template--16988185755808__product-grid"
                                                                    data-cart-quantity="0" data-min="1" min="1"
                                                                    step="1" value="1"
                                                                    form="product-form-7039416696992" />
                                                                <button class="quantity__button no-js-hidden"
                                                                    name="plus" type="button">
                                                                    <span class="visually-hidden">Increase quantity for AES
                                                                        Class 1 Summer
                                                                        Boys Tie ~ 43 - 7571</span>
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        aria-hidden="true" focusable="false"
                                                                        class="icon icon-plus" fill="none"
                                                                        viewbox="0 0 10 10">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M1 4.51a.5.5 0 000 1h3.5l.01 3.5a.5.5 0 001-.01V5.5l3.5-.01a.5.5 0 00-.01-1H5.5L5.49.99a.5.5 0 00-1 .01v3.5l-3.5.01H1z"
                                                                            fill="currentColor"></path>
                                                                    </svg>
                                                                </button>
                                                            </quantity-input>
                                                            <div class="quantity__rules caption no-js-hidden"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card__badge bottom left"></div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>

                            <div class="pagination-wrapper">
                                <nav class="pagination" role="navigation" aria-label="Pagination">
                                    <ul class="pagination__list list-unstyled" role="list">
                                        <li>
                                            <a role="link" aria-disabled="true"
                                                class="pagination__item pagination__item--current light"
                                                aria-current="page" aria-label="Page 1" href="collections.html">1</a>
                                        </li>
                                        <li>
                                            <a href="all?page=2.html" class="pagination__item link"
                                                aria-label="Page 2">2</a>
                                        </li>
                                        <li>
                                            <a href="all?page=3.html" class="pagination__item link"
                                                aria-label="Page 3">3</a>
                                        </li>
                                        <li><span class="pagination__item">&hellip;</span></li>
                                        <li>
                                            <a href="all?page=420.html" class="pagination__item link"
                                                aria-label="Page 420">420</a>
                                        </li>
                                        <li>
                                            <a href="all?page=2.html"
                                                class="pagination__item pagination__item--prev pagination__item-arrow link motion-reduce"
                                                aria-label="Next page"><svg aria-hidden="true" focusable="false"
                                                    class="icon icon-caret" viewbox="0 0 10 6">
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                        d="M9.354.646a.5.5 0 00-.708 0L5 4.293 1.354.646a.5.5 0 00-.708.708l4 4a.5.5 0 00.708 0l4-4a.5.5 0 000-.708z"
                                                        fill="currentColor"></path>
                                                </svg>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection


@section('script')
@endsection
