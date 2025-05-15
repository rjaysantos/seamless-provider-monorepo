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

    .preview iframe {
        width: 100%;
        height: 500px;
        border: none;
    }

    .table-row.preview .table-cell {
        padding: 0;
        border: none;
    }
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<body>
    <div class="table">
        <div class="table-row table-header">
            <div class="table-cell">ROUND ID - {{ $trxID }}</div>
            <div class="table-cell">BET AMOUNT</div>
            <div class="table-cell">WIN AMOUNT</div>
        </div>

        @foreach ($roundData as $roundDetail)
            <div class="table-row" data-trx-id="{{ $roundDetail['roundID'] }}">
                <div class="table-cell">{{ $roundDetail['roundID'] }}</div>
                <div class="table-cell">{{ $roundDetail['bet'] }}</div>
                <div class="table-cell">{{ $roundDetail['win'] }}</div>
            </div>
        @endforeach
    </div>

    <script>
        const BASE_URL = "{{ request()->getSchemeAndHttpHost() }}";
        const playID = "{{ $playID }}";
        const currency = "{{ $currency }}";

        $(document).ready(function () {
            $('.table-row').not('.table-header').on('click', function () {
                const trxID = $(this).data('trx-id');
                const $clickedRow = $(this);

                $('.table-row.preview').remove();

                $.ajax({
                    url: `${BASE_URL}/hg5/in/visual/fishgame/`,
                    method: 'GET',
                    data: {
                        trxID: trxID,
                        playID: playID,
                        currency: currency
                    },
                    success: function (response) {
                        let content = '';

                        if (response.success && response.data) {
                            const url = response.data

                            content = `
                                <div class="table-row preview">
                                    <div class="table-cell" style="width: 100%" colspan="3">
                                        <iframe src="${url}"></iframe>
                                    </div>
                                </div>
                            `;
                        } else {
                            content = `
                                <div class="table-row preview">
                                    <div class="table-cell" colspan="3" style="padding: 20px;
                                                color: red; text-align: center; width: 100%">
                                        Something went wrong. Please try again.
                                    </div>
                                </div>
                            `;
                        }

                        $clickedRow.after(content);
                    },
                    error: function (xhr, status, error) {
                        console.log('failed');
                    }
                });
            });
        });
    </script>
</body>

</html>