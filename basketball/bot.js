'use strict';

var express = require('express'),
    app = express(),
    jsdom = require('jsdom'),
    Games = require('./db/db');

var addData = function (game, team, totalPoints, periodPoints, pos, $, time) {
    if(typeof time !== 'undefined'){
        game.time = game.time !== time ? time : game.time;
    }                                    
    game['team'+pos] = game['team'+pos] !== team ? team : game['team'+pos];
    game['totalPoints'+pos] = game['totalPoints'+pos] !== totalPoints ? totalPoints : game['totalPoints'+pos];
    game['firstPeriodPoints'+pos] = $(periodPoints[0]).text().trim();
    game['secondPeriodPoints'+pos] = $(periodPoints[1]).text().trim();
    game['thirdPeriodPoints'+pos] = $(periodPoints[2]).text().trim();
    game['fourthPeriodPoints'+pos] = $(periodPoints[3]).text().trim();
    game['fifthPeriodPoints'+pos] = $(periodPoints[4]).text().trim();

    return game;
}


jsdom.env({
    url : 'http://www.livescore.com/basketball/',
    scripts : ['https://code.jquery.com/jquery-2.1.1.min.js'],
    done : function (err, window) {
        var $ = window.jQuery,
            game, league, date;
        $('.league-multi').each(function () {
            var cont = 0,
                teamsArray = [];
            league = $(this).find('.league').text().trim();
            date = $(this).find('.date').text().trim();

            $(this).find('tr').each(function () {
                if($(this).attr('class') === 'even' || $(this).attr('class') === '') {

                    // Se obtienen datos locales del primero de los equipos del juego
                    var time = $(this).find('.fd').text().trim(),
                        team1 = $(this).find('.ft').text().trim(),
                        totalPoints1 = $(this).find('.fs').text().trim(),
                        periodPoints1 = $(this).find('.fp');

                    // Se guardan en un arreglo para poder acceder a los datos luego en el
                    // closure de la función callback del query a la bd
                    teamsArray[cont] = {};
                    teamsArray[cont].league = league;
                    teamsArray[cont].date = date;
                    teamsArray[cont].time = time;
                    teamsArray[cont].team1 = team1;
                    teamsArray[cont].totalPoints1 = totalPoints1;
                    teamsArray[cont].periodPoints1 = periodPoints1;

                } else if($(this).attr('class') === 'awy ' || $(this).attr('class') === 'awy even') {

                    // Se obtienen datos locales del segundo de los equipos del juego
                    var team2 = $(this).find('.ft').text().trim(),
                        totalPoints2 = $(this).find('.fs').text().trim(),
                        periodPoints2 = $(this).find('.fp'),
                        localCont = cont; 
                        // Esta última es una variable contadora local que identifica
                        // la posición actual en el closure de la función callback

                    // Se guardan los datos del segundo equipo en el arreglo anterior
                    // para completar los datos del juego que se está procesando
                    teamsArray[cont].team2 = team2;
                    teamsArray[cont].totalPoints2 = totalPoints2;
                    teamsArray[cont].periodPoints2 = periodPoints2;

                    Games.findOne({
                        league : teamsArray[cont].league, 
                        date : teamsArray[cont].date, 
                        team1 : teamsArray[cont].team1
                    }, function (err, game) {
                        var data = teamsArray[localCont];

                        if(!game) {
                            game = new Games({
                                league : data.league,
                                date : data.date 
                            });                                        
                        }

                        game = addData(game, data.team1, data.totalPoints1, data.periodPoints1, 1, $, data.time);

                        game = addData(game, data.team2, data.totalPoints2, data.periodPoints2, 2, $);

                        game.save();
                    });
                    cont += 1;
                }
            });
        });
    }
});