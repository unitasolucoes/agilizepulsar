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
        console.log('🚀 PulsarLike.toggle - INÍCIO');
        console.log('   ticketId:', ticketId);
        
        var token = getGLPICSRFToken();
        console.log('   Token CSRF:', token ? 'ENCONTRADO' : 'NÃO ENCONTRADO');

        var payload = new URLSearchParams();
        payload.append('action', 'toggle');
        payload.append('ticket_id', ticketId);

        if (token) {
            payload.append('_glpi_csrf_token', token);
        }

        console.log('📦 Payload:', payload.toString());

        var ajaxUrl = '/plugins/agilizepulsar/front/test_like_ultra_simple.php';
        console.log('🎯 URL:', ajaxUrl);

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: payload.toString()
        })
        .then(function(response) {
            console.log('📡 Status HTTP:', response.status);
            console.log('📡 Response ok:', response.ok);
            
            return response.text(); // ✅ Pegar como texto primeiro
        })
        .then(function(text) {
            console.log('📄 Resposta bruta (primeiros 500 caracteres):');
            console.log(text.substring(0, 500));
            console.log('📏 Tamanho total:', text.length, 'caracteres');
            
            // Tentar parsear JSON
            try {
                const data = JSON.parse(text);
                console.log('✅ JSON parseado com sucesso:', data);
                
                if (callback) {
                    callback(data);
                }
            } catch (e) {
                console.error('❌ ERRO ao parsear JSON:', e);
                console.error('📄 Texto completo que causou erro:');
                console.error(text);
                
                if (callback) {
                    callback({
                        success: false,
                        message: 'Resposta inválida do servidor'
                    });
                }
            }
        })
        .catch(function(error) {
            console.error('💥 ERRO na requisição:', error);
            if (callback) {
                callback({
                    success: false,
                    message: error && error.message ? error.message : 'Erro ao processar curtida'
                });
            }
        });
    }
};

var PulsarComment = {
    add: function(ticketId, content, callback) {
        var token = getGLPICSRFToken();
        
        var formData = new URLSearchParams();
        formData.append('ticket_id', ticketId);
        formData.append('content', content);
        
        if (token) {
            formData.append('_glpi_csrf_token', token);
        }

        var ajaxUrl = '/plugins/agilizepulsar/front/add_comment.php';

        console.log('🚀 Enviando comentário para:', ajaxUrl);
        console.log('📦 Dados:', {ticketId, content});

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
        })
        .then(response => {
            console.log('📡 Status HTTP:', response.status);
            
            return response.text().then(text => {
                console.log('📄 Resposta bruta:', text.substring(0, 200));
                
                try {
                    const data = JSON.parse(text);
                    console.log('✅ JSON parseado:', data);
                    
                    if (!response.ok) {
                        console.error('❌ Erro HTTP:', response.status, data);
                        throw new Error(data.message || 'Erro na requisição: ' + response.status);
                    }
                    
                    return data;
                } catch (e) {
                    console.error('❌ Erro ao parsear JSON:', e);
                    console.error('📄 Resposta não era JSON:', text);
                    throw new Error('Resposta inválida do servidor');
                }
            });
        })
        .then(data => {
            console.log('✅ Sucesso!', data);
            if (callback) callback(data);
        })
        .catch(error => {
            console.error('💥 ERRO FINAL:', error);
            if (callback) {
                callback({
                    success: false, 
                    message: error.message || 'Erro ao adicionar comentário'
                });
            }
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

var PulsarUtils = {
    copyToClipboard: function(text, callback) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                if (callback) callback(true);
            }).catch(function(err) {
                console.error('Erro ao copiar:', err);
                if (callback) callback(false);
            });
        } else {
            var textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                if (callback) callback(true);
            } catch (err) {
                console.error('Erro ao copiar:', err);
                if (callback) callback(false);
            }
            document.body.removeChild(textArea);
        }
    },

    timeAgo: function(dateString) {
        var date = new Date(dateString);
        var now = new Date();
        var seconds = Math.floor((now - date) / 1000);
        
        var intervals = {
            'ano': 31536000,
            'mês': 2592000,
            'semana': 604800,
            'dia': 86400,
            'hora': 3600,
            'minuto': 60
        };
        
        for (var key in intervals) {
            var interval = Math.floor(seconds / intervals[key]);
            if (interval >= 1) {
                return interval + ' ' + key + (interval > 1 ? 's' : '') + ' atrás';
            }
        }
        
        return 'agora';
    },

    scrollToElement: function(selector, offset) {
        offset = offset || 0;
        var element = document.querySelector(selector);
        if (element) {
            var elementPosition = element.getBoundingClientRect().top;
            var offsetPosition = elementPosition + window.pageYOffset - offset;
            
            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }
    },

    debounce: function(func, wait) {
        var timeout;
        return function executedFunction() {
            var context = this;
            var args = arguments;
            var later = function() {
                timeout = null;
                func.apply(context, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

var PulsarCampaign = {
    request: function(payload) {
        var token = getGLPICSRFToken();
        payload = payload || {};
        if (token) {
            payload._glpi_csrf_token = token;
        }

        return fetch('../ajax/link_campaign.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(payload).toString()
        }).then(function(response) {
            return response.json().catch(function() {
                throw new Error('Resposta inválida do servidor');
            }).then(function(data) {
                if (!response.ok && data && typeof data.message === 'string') {
                    throw new Error(data.message);
                }
                return data;
            });
        });
    },

    link: function(ideaId, campaignId, callback) {
        this.request({
            action: 'link',
            idea_id: ideaId,
            campaign_id: campaignId
        }).then(function(data) {
            if (callback) {
                callback(data);
            }
        }).catch(function(error) {
            console.error('Erro ao vincular ideia:', error);
            if (callback) {
                callback({
                    success: false,
                    message: error.message || 'Erro ao vincular ideia à campanha'
                });
            }
        });
    },

    unlink: function(ideaId, callback) {
        this.request({
            action: 'unlink',
            idea_id: ideaId
        }).then(function(data) {
            if (callback) {
                callback(data);
            }
        }).catch(function(error) {
            console.error('Erro ao desvincular ideia:', error);
            if (callback) {
                callback({
                    success: false,
                    message: error.message || 'Erro ao desvincular ideia da campanha'
                });
            }
        });
    },

    getCampaigns: function(callback) {
        this.request({
            action: 'get_campaigns'
        }).then(function(data) {
            if (callback) {
                callback(data);
            }
        }).catch(function(error) {
            console.error('Erro ao buscar campanhas:', error);
            if (callback) {
                callback({
                    success: false,
                    message: error.message || 'Erro ao buscar campanhas'
                });
            }
        });
    },

    openModal: function(ideaId, currentCampaignId) {
        var self = this;
        this.getCampaigns(function(response) {
            if (!response.success) {
                alert(response.message || 'Não foi possível carregar as campanhas.');
                return;
            }

            self.createModal(ideaId, Array.isArray(response.campaigns) ? response.campaigns : [], currentCampaignId);
        });
    },

    createModal: function(ideaId, campaigns, currentCampaignId) {
        var existingModal = document.getElementById('campaign-link-modal');
        if (existingModal) {
            existingModal.remove();
        }

        var modal = document.createElement('div');
        modal.id = 'campaign-link-modal';
        modal.className = 'pulsar-modal';

        var escapeHtml = function(text) {
            if (text === null || text === undefined) {
                return '';
            }

            return String(text).replace(/[&<>"']/g, function(char) {
                switch (char) {
                    case '&':
                        return '&amp;';
                    case '<':
                        return '&lt;';
                    case '>':
                        return '&gt;';
                    case '"':
                        return '&quot;';
                    case "'":
                        return '&#39;';
                    default:
                        return char;
                }
            });
        };

        var campaignsHTML = '';
        if (!campaigns.length) {
            campaignsHTML = '<p class="empty-message">Nenhuma campanha ativa encontrada.</p>';
        } else {
            campaigns.forEach(function(campaign) {
                var selected = String(campaign.id) === String(currentCampaignId);
                var deadline = campaign.deadline || 'Sem prazo definido';
                campaignsHTML += '\n                    <div class="campaign-option' + (selected ? ' selected' : '') + '" data-campaign-id="' + campaign.id + '">\n                        <div class="campaign-option-info">\n                            <h4>' + escapeHtml(campaign.name) + '</h4>\n                            <p class="campaign-deadline"><i class="fa-solid fa-calendar"></i> Prazo: ' + escapeHtml(deadline) + '</p>\n                            <p class="campaign-status"><i class="fa-solid fa-circle-info"></i> ' + escapeHtml(campaign.status || '') + '</p>\n                        </div>\n                        <button class="btn-select" data-campaign-id="' + campaign.id + '">' + (selected ? 'Selecionada' : 'Selecionar') + '</button>\n                    </div>';
            });
        }

        modal.innerHTML = '\n            <div class="modal-overlay"></div>\n            <div class="modal-content">\n                <div class="modal-header">\n                    <h2><i class="fa-solid fa-flag"></i> Vincular a uma Campanha</h2>\n                    <button class="modal-close"><i class="fa-solid fa-times"></i></button>\n                </div>\n                <div class="modal-body">' + campaignsHTML + '</div>\n                <div class="modal-footer">' + (currentCampaignId ? '<button class="btn-unlink">Desvincular Campanha</button>' : '') + '<button class="btn-cancel">Cancelar</button></div>\n            </div>';

        document.body.appendChild(modal);

        var closeModal = function() {
            modal.classList.remove('show');
            setTimeout(function() {
                modal.remove();
            }, 200);
        };

        modal.querySelector('.modal-overlay').addEventListener('click', closeModal);
        modal.querySelector('.modal-close').addEventListener('click', closeModal);
        modal.querySelector('.btn-cancel').addEventListener('click', closeModal);

        modal.querySelectorAll('.btn-select').forEach(function(button) {
            button.addEventListener('click', function() {
                var selectedButton = this;
                var campaignId = selectedButton.getAttribute('data-campaign-id');

                selectedButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Vinculando...';
                selectedButton.disabled = true;

                PulsarCampaign.link(ideaId, campaignId, function(response) {
                    if (response.success) {
                        closeModal();
                        alert(response.message || 'Ideia vinculada com sucesso!');
                        window.location.reload();
                    } else {
                        alert(response.message || 'Não foi possível vincular a ideia.');
                        selectedButton.innerHTML = 'Selecionar';
                        selectedButton.disabled = false;
                    }
                });
            });
        });

        var unlinkButton = modal.querySelector('.btn-unlink');
        if (unlinkButton) {
            unlinkButton.addEventListener('click', function() {
                if (!confirm('Deseja realmente desvincular esta ideia da campanha?')) {
                    return;
                }

                unlinkButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Desvinculando...';
                unlinkButton.disabled = true;

                PulsarCampaign.unlink(ideaId, function(response) {
                    if (response.success) {
                        closeModal();
                        alert(response.message || 'Ideia desvinculada com sucesso!');
                        window.location.reload();
                    } else {
                        alert(response.message || 'Não foi possível desvincular a ideia.');
                        unlinkButton.innerHTML = 'Desvincular Campanha';
                        unlinkButton.disabled = false;
                    }
                });
            });
        }

        setTimeout(function() {
            modal.classList.add('show');
        }, 10);
    }
};