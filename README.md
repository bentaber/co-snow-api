# co-snow-api

Sever side API app for https://cosnow.bentaber.com/

Built for fun to play with some new datasets and technologies.

* All data sourced from [SNOTEL](https://www.wcc.nrcs.usda.gov/snow/) reports.
* Server API application built in PHP 7 using [Laravel Lumen](https://lumen.laravel.com/) framework.
* Mapping provided via the [Mapbox](https://docs.mapbox.com/mapbox-gl-js/api/) WebGL API.
* Snow depth APIs return data in [GeoJSON](https://tools.ietf.org/html/rfc7946) format in order to play nicely with the Mapbox API.
* Data stored in PostgreSQL.
* Client side application utilizing [Vue.js](https://vuejs.org/) for interaction and reactivity

## Install

```
  > git clone
  > cd co-snow-api
  > composer install
```

## Run

Add your own .env file.

```
  > php -S localhost:8000 -t public
```

TEST!

TEst 2!
