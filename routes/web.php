<?php

$router->get('/api/sites', 'APIController@sites');

$router->get(
  '/api/measurements/{stationId}/{startDate}/{count}',
  'APIController@measurements'
);

$router->get('/api/geoJSON/{date}', 'APIController@geoJSON');
