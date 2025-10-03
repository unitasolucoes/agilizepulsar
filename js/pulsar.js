function getGLPICSRFToken() {
    var metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken) {
        return metaToken.getAttribute('content');
    }

    var inputToken = document.querySelector('input[name="_glpi_csrf_token"]');
    if (inputToken) {
        return inputToken.value;
    }

    return '';
}

var PulsarLike = {
    toggle: function(ticketId, callback) {
        var token = encodeURIComponent(getGLPICSRFToken());
        fetch('../ajax/like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=toggle&ticket_id=' + ticketId + '&_glpi_csrf_token=' + token
        })
        .then(response => response.json())
        .then(data => {
            if (callback) callback(data);
        })
        .catch(error => {
            console.error('Error:', error);
            if (callback) callback({success: false, message: 'Erro ao processar curtida'});
        });
    }
};

var PulsarComment = {
    add: function(ticketId, content, callback) {
        var token = encodeURIComponent(getGLPICSRFToken());
        fetch('../ajax/comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=add&ticket_id=' + ticketId + '&content=' + encodeURIComponent(content) + '&_glpi_csrf_token=' + token
        })
        .then(response => response.json())
        .then(data => {
            if (callback) callback(data);
        })
        .catch(error => {
            console.error('Error:', error);
            if (callback) callback({success: false, message: 'Erro ao adicionar comentário'});
        });
    }
};

var PulsarRanking = {
    get: function(period, limit, callback) {
        var token = encodeURIComponent(getGLPICSRFToken());
        fetch('../ajax/ranking.php?period=' + period + '&limit=' + limit + '&_glpi_csrf_token=' + token)
        .then(response => response.json())
        .then(data => {
            if (callback) callback(data);
        })
        .catch(error => {
            console.error('Error:', error);
            if (callback) callback({success: false, message: 'Erro ao buscar ranking'});
        });
    }
};