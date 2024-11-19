<?php

declare(strict_types=1);
/*
-------------------------------------------------------------------------------
les routes
-------------------------------------------------------------------------------
 */

return [
    // ['', '/client-php/curl', 'curl@index'],
    
    ['GET', '/api/v1/fake-datas/posts', 'post@index'],

    ['GET', '/api/v1/fake-datas/posts/{id:\d+}', 'post@show'],
    
    ['POST', '/api/v1/fake-datas/posts', 'post@create'],  
    ['PATCH', '/api/v1/fake-datas/posts/{id:\d+}', 'post@update'],  
    ['DELETE', '/api/v1/fake-datas/posts/{id:\d+}', 'post@delete'],  

    ///// ADMIN /////

    ///// CREATEUR /////

    ///// DECK /////

    ///// CARTE /////

    /// GET ///
    ['GET', '/api/v1/cartes/admin', 'carte@getCartesForAdmin'], 
    ['GET', '/api/v1/cartes/createur/{id}', 'carte@getCartesForCreateur'],
    ['GET', '/api/v1/cartes/{id:\d+}', 'carte@index'], 
    ['GET', '/api/v1/createur/cartes', 'carte@index'],  
    ['GET', '/api/v1/admin/cartes', 'carte@index'],  
    
    /// POST ///

    /// PATCH ///
    
    /// DELETE ///
    
    ///// CARTE ALEATOIRE /////

    ///// CREATEUR BANNI /////

];
