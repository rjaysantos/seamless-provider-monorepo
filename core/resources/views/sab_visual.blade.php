<html>
<style>
    .table {
        display: table;
        width: 100%;
        border-collapse: collapse;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .table-row {
        display: table-row;
    }

    .table-cell {
        display: table-cell;
        border: 1px solid #ffffff;
        padding: 10px;
        text-align: left;
        font-size: small;
    }

    .table-header {
        font-weight: bold;
        background-color: #4287f5;
    }
</style>

<body>
    <div class="table">
        <div class="table-row table-header">
            <div class="table-cell">
                BET TICKET
            </div>
            <div class="table-cell">
                EVENT
            </div>
            <div class="table-cell">
                MATCH
            </div>
            <div class="table-cell">
                BET TYPE
            </div>
            <div class="table-cell">
                BET CHOICE
            </div>
            <div class="table-cell">
                HDP
            </div>
            <div class="table-cell">
                ODDS
            </div>
            <div class="table-cell">
                ODDS TYPE
            </div>
            <div class="table-cell">
                BETS AMOUNT
            </div>
            <div class="table-cell">
                SCORE
            </div>
            <div class="table-cell">
                STATUS
            </div>
        </div>
        <div class="table-row">
            <div class="table-cell">
                Ticket ID: {{$ticketID}} </br>
                {{$dateTimeSettle}} GMT-4 </br>
                Ip Address: -
            </div>
            <div class="table-cell">
                {{$event}}
            </div>
            <div class="table-cell">
                {{$match}}
            </div>
            <div class="table-cell">
                {{$betType}}
            </div>
            <div class="table-cell">
                {{$betChoice}}
            </div>
            <div class="table-cell">
                {{$hdp}}
            </div>
            <div class="table-cell">
                {{$odds}}
            </div>
            <div class="table-cell">
                {{$oddsType}}
            </div>
            <div class="table-cell">
                {{$betAmount}}
            </div>
            <div class="table-cell">
                {{$score}}
            </div>
            <div class="table-cell">
                {{$status}}
            </div>
        </div>
    </div>

    @if (!empty($mixParleyData))
    <div class="table">
        <h3>Mix Parlay Details</h3>
        <div class="table-row table-header">
            <div class="table-cell">
                EVENT
            </div>
            <div class="table-cell">
                MATCH
            </div>
            <div class="table-cell">
                BET TYPE
            </div>
            <div class="table-cell">
                BET CHOICE
            </div>
            <div class="table-cell">
                HDP
            </div>
            <div class="table-cell">
                ODDS
            </div>
            <div class="table-cell">
                SCORE
            </div>
            <div class="table-cell">
                STATUS
            </div>
        </div>

        @foreach ($mixParleyData as $betDetail)
        <div class="table-row">
            <div class="table-cell">
                {{$betDetail->event}}
            </div>
            <div class="table-cell">
                {{$betDetail->match}}
            </div>
            <div class="table-cell">
                {{$betDetail->betType}}
            </div>
            <div class="table-cell">
                {{$betDetail->betChoice}}
            </div>
            <div class="table-cell">
                {{$betDetail->hdp}}
            </div>
            <div class="table-cell">
                {{$betDetail->odds}}
            </div>
            <div class="table-cell">
                {{$betDetail->score}}
            </div>
            <div class="table-cell">
                {{$betDetail->status}}
            </div>
        </div>
        @endforeach

    </div>
    @endif

    @if (!empty($singleParleyData))
    <div class="table">
        <h3>Single Parlay Details</h3>
        <div class="table-row table-header">
            <div class="table-cell">
                BET CHOICE
            </div>
            <div class="table-cell">
                STATUS
            </div>
        </div>

        @foreach ($singleParleyData as $betDetail)
        <div class="table-row">
            <div class="table-cell">
                {{$betDetail->betChoice}}
            </div>
            <div class="table-cell">
                {{$betDetail->status}}
            </div>
        </div>
        @endforeach

    </div>
    @endif

</body>

</html>