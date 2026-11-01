(function () {
    'use strict';

    var DATA_URL = './course-changes-summary.json';

    var state = {
        racers: [],
        sortKey: 'rank',
        sortDirection: 'ascending',
    };

    var elements = {
        generatedAt: document.getElementById('generated-at'),
        filter: document.getElementById('filter'),
        count: document.getElementById('count'),
        error: document.getElementById('error'),
        body: document.getElementById('racers-body'),
        headers: document.querySelectorAll('#racers-table thead th'),
    };

    function formatGeneratedAt(isoString) {
        var date = new Date(isoString);

        if (isNaN(date.getTime())) {
            return '';
        }

        return '最終更新: ' + new Intl.DateTimeFormat('ja-JP', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(date);
    }

    function formatRate(rate) {
        return (rate * 100).toFixed(1) + '%';
    }

    function rateColor(rate) {
        var cold = [148, 163, 184];
        var hot = [217, 45, 32];
        var t = Math.max(0, Math.min(1, rate));

        var rgb = cold.map(function (channel, i) {
            return Math.round(channel + (hot[i] - channel) * t);
        });

        return rgb.join(' ');
    }

    function assignRanks(racers) {
        var byRate = racers.slice().sort(function (a, b) {
            return b.outer_race_rate - a.outer_race_rate;
        });

        var rank = 0;
        var previousRate = null;

        byRate.forEach(function (racer, index) {
            if (racer.outer_race_rate !== previousRate) {
                rank = index + 1;
                previousRate = racer.outer_race_rate;
            }

            racer.rank = rank;
        });
    }

    function sortRacers(racers) {
        var key = state.sortKey;
        var direction = state.sortDirection === 'ascending' ? 1 : -1;

        return racers.slice().sort(function (a, b) {
            if (a[key] < b[key]) {
                return -1 * direction;
            }

            if (a[key] > b[key]) {
                return 1 * direction;
            }

            return 0;
        });
    }

    function filterRacers(racers, query) {
        if (!query) {
            return racers;
        }

        var needle = query.trim().toLowerCase();

        return racers.filter(function (racer) {
            return String(racer.number).includes(needle) || String(racer.name || '').toLowerCase().includes(needle);
        });
    }

    function renderTable() {
        var filtered = filterRacers(state.racers, elements.filter.value);
        var sorted = sortRacers(filtered);

        elements.body.textContent = '';

        sorted.forEach(function (racer) {
            var row = document.createElement('tr');

            row.appendChild(createCell(String(racer.rank), true));
            row.appendChild(createCell(String(racer.number), true));
            row.appendChild(createCell(racer.name, false));
            row.appendChild(createCell(String(racer.race_count), true));
            row.appendChild(createCell(String(racer.outer_race_count), true));
            row.appendChild(createRateCell(racer.outer_race_rate));
            row.appendChild(createCell(String(racer.inner_race_count), true));
            row.appendChild(createCell(formatRate(racer.inner_race_rate), true));

            elements.body.appendChild(row);
        });

        elements.count.textContent = sorted.length + ' 名';
    }

    function createCell(text, numeric) {
        var cell = document.createElement('td');
        cell.textContent = text;

        if (numeric) {
            cell.className = 'numeric';
        }

        return cell;
    }

    function createRateCell(rate) {
        var cell = document.createElement('td');
        cell.className = 'numeric';

        var badge = document.createElement('span');
        badge.className = 'rate-badge';
        badge.textContent = formatRate(rate);
        badge.style.setProperty('--rate-color', rateColor(rate));

        cell.appendChild(badge);

        return cell;
    }

    function updateHeaderIndicators() {
        elements.headers.forEach(function (header) {
            if (header.dataset.key === state.sortKey) {
                header.setAttribute('aria-sort', state.sortDirection);
            } else {
                header.removeAttribute('aria-sort');
            }
        });
    }

    function onHeaderClick(event) {
        var key = event.currentTarget.dataset.key;

        if (state.sortKey === key) {
            state.sortDirection = state.sortDirection === 'ascending' ? 'descending' : 'ascending';
        } else {
            state.sortKey = key;
            state.sortDirection = 'ascending';
        }

        updateHeaderIndicators();
        renderTable();
    }

    function showError(message) {
        elements.error.textContent = message;
        elements.error.hidden = false;
    }

    function init() {
        elements.headers.forEach(function (header) {
            header.addEventListener('click', onHeaderClick);
        });

        elements.filter.addEventListener('input', renderTable);

        fetch(DATA_URL)
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return response.json();
            })
            .then(function (data) {
                state.racers = Object.values(data.racers);
                assignRanks(state.racers);
                elements.generatedAt.textContent = formatGeneratedAt(data.generated_at);
                updateHeaderIndicators();
                renderTable();
            })
            .catch(function (error) {
                showError('データの読み込みに失敗しました: ' + error.message);
            });
    }

    init();
})();
