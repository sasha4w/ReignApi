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

    /// GET ///
       
    /// POST ///
    ['POST', '/api/v1/administrateur', 'administrateur@create'],
    ['POST', '/api/v1/administrateur/login', 'administrateur@login'],

    /// PATCH ///
    
    /// DELETE ///

    ///// CREATEUR /////
    

    /// GET ///
       
    /// POST ///
    ['POST', '/api/v1/createur', 'createur@create'],
    ['POST', '/api/v1/createur/login', 'createur@login'],


    /// PATCH ///
    ['PATCH', '/api/v1/createurs/warn/{id_createur:\d+}', 'createur@warn'],

    /// DELETE ///

    ///// DECK /////

    /// GET ///
    
    ['GET', '/api/v1/decks', 'deck@getDecks'], 
   
    /// POST ///
    ['POST', '/api/v1/deck', 'deck@create'],

    /// PATCH ///
    ['PATCH', '/api/v1/decks/{id_deck:\d+}', 'deck@update'],

    /// DELETE ///
    ['DELETE', '/api/v1/decks/{id_deck:\d+}', 'deck@delete'],

    ///// CARTE /////

    /// GET ///
    ['GET', '/api/v1/cartes/administrateur', 'carte@getCartesForAdmin'], 
    ['GET', '/api/v1/cartes/createur/{id}', 'carte@getCartesForCreateur'],

    
    /// POST ///
    ['POST', '/api/v1/carte', 'carte@create'],

    /// PATCH ///
    ['PATCH', '/api/v1/cartes/{id_carte:\d+}', 'carte@update'],

    
    /// DELETE ///
 

    ///// CARTE ALEATOIRE /////
    
    /// GET ///
       
    /// POST ///

    /// PATCH ///
    
    /// DELETE ///

    ///// CREATEUR BANNI /////
    
    /// GET ///
       
    /// POST ///

    /// PATCH ///
    
    /// DELETE ///

];
