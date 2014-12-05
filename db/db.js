var mongoose = require('mongoose');

mongoose.connect('mongodb://localhost/basketball');

var gamesSchema = mongoose.Schema({
    league : String,
    date : String,
    time : String,
    team1 : String,
    team2 : String,
    totalPoints1 : String,
    totalPoints2 : String,
    firstPeriodPoints1 : String,
    firstPeriodPoints2 : String,
    secondPeriodPoints1 : String,
    secondPeriodPoints2 : String,
    thirdPeriodPoints1 : String,
    thirdPeriodPoints2 : String,
    fourthPeriodPoints1 : String,
    fourthPeriodPoints2 : String,
    fifthPeriodPoints1 : String,
    fifthPeriodPoints2 : String,
});

module.exports = mongoose.model('games', gamesSchema);