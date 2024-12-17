<?php

declare(strict_types=1);
/*
-------------------------------------------------------------------------------
les routes
-------------------------------------------------------------------------------
 */

return [
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
    ['GET', '/api/v1/decks/playable', 'deck@getPlayableDecks'], 
   
    /// POST ///
    ['POST', '/api/v1/deck', 'deck@create'],

    /// PATCH ///
    ['PATCH', '/api/v1/decks/{id_deck:\d+}', 'deck@update'],
    ['PATCH', '/api/v1/decks/like/{id_deck:\d+}', 'deck@like'],


    /// DELETE ///
    ['DELETE', '/api/v1/decks/{id_deck:\d+}', 'deck@delete'],

    ///// CARTE /////

    /// GET ///
    ['GET', '/api/v1/cartes/administrateur', 'carte@getCartesForAdmin'], 
    ['GET', '/api/v1/cartes/createur/{id}', 'carte@getCartesForCreateur'],
    ['GET', '/api/v1/cartes/{id}', 'carte@getCartesForOneDeck'],
    
    /// POST ///
    ['POST', '/api/v1/carte', 'carte@create'],

    /// PATCH ///
    ['PATCH', '/api/v1/cartes/{id_carte:\d+}', 'carte@update'],

    
    /// DELETE ///
    ['DELETE', '/api/v1/cartes/{id_carte:\d+}', 'carte@delete'],
 

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
