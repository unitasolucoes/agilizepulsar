var PulsarLike = {
    toggle: function(ticketId, callback) {
        fetch('../ajax/like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=toggle&ticket_id=' + ticketId
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
        fetch('../ajax/comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=add&ticket_id=' + ticketId + '&content=' + encodeURIComponent(content)
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
        fetch('../ajax/ranking.php?period=' + period + '&limit=' + limit)
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