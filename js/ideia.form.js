(function () {
  'use strict';

  const initTinyMCE = () => {
    if (typeof tinymce === 'undefined') {
      console.error('TinyMCE is not loaded');
      return;
    }

    tinymce.init({
      selector: '#form-nova-ideia .tinymce-editor',
      menubar: false,
      branding: false,
      statusbar: true,
      height: 220,
      language: 'pt_BR',
      plugins: 'lists link table autoresize',
      toolbar: 'undo redo | bold italic underline | bullist numlist | link table | removeformat',
      convert_urls: false,
      setup: (editor) => {
        editor.on('change', () => {
          editor.save();
        });
      }
    });
  };

  const formatDeadline = (deadline) => {
    if (!deadline) {
      return null;
    }

    try {
      const date = new Date(deadline.replace(' ', 'T'));
      if (Number.isNaN(date.getTime())) {
        return null;
      }

      return date.toLocaleDateString('pt-BR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
      });
    } catch (e) {
      return null;
    }
  };

  const setupCampaignPreview = (form) => {
    const select = form.querySelector('select[name="campanha_id"]');
    const preview = form.querySelector('#campaign-preview');

    if (!select || !preview) {
      return;
    }

    const updatePreview = () => {
      const option = select.options[select.selectedIndex];
      if (!option || !option.dataset.deadline) {
        preview.style.display = 'none';
        preview.innerHTML = '';
        return;
      }

      const deadline = formatDeadline(option.dataset.deadline);
      preview.innerHTML = `
        <h3>${option.textContent}</h3>
        <p><strong>Prazo estimado:</strong> ${deadline ?? 'Não definido'}</p>
      `;
      preview.style.display = 'block';
    };

    select.addEventListener('change', updatePreview);
    updatePreview();
  };

  const toggleSubmitState = (button, isLoading) => {
    if (!button) {
      return;
    }

    if (isLoading) {
      button.dataset.originalText = button.textContent;
      button.innerHTML = '<span class="loading-spinner"></span>Enviando...';
      button.disabled = true;
    } else {
      const text = button.dataset.originalText || 'Enviar';
      button.textContent = text;
      button.disabled = false;
    }
  };

  const validateForm = (form) => {
    const titulo = form.querySelector('input[name="titulo"]');
    const campanha = form.querySelector('select[name="campanha_id"]');
    const areaImpactada = form.querySelector('select[name="area_impactada"]');
    const descricao = form.querySelector('textarea[name="descricao"]');
    const beneficios = form.querySelector('textarea[name="beneficios"]');
    const radiosIdeiaExistente = form.querySelectorAll('input[name="ideia_existente"]');
    const radiosClassificacao = form.querySelectorAll('input[name="classificacao"]');
    const objetivo = form.querySelector('input[name="objetivo_estrategico"]');

    const getRadioValue = (radios) => {
      return Array.from(radios).some((radio) => radio.checked);
    };

    if (!titulo.value.trim()) {
      alert('Informe o título da ideia.');
      titulo.focus();
      return false;
    }

    if (!campanha.value) {
      alert('Selecione uma campanha.');
      campanha.focus();
      return false;
    }

    if (!areaImpactada.value) {
      alert('Selecione a área impactada.');
      areaImpactada.focus();
      return false;
    }

    if (!descricao.value.trim()) {
      alert('Informe a descrição da ideia.');
      descricao.focus();
      return false;
    }

    if (!beneficios.value.trim()) {
      alert('Informe os benefícios esperados.');
      beneficios.focus();
      return false;
    }

    if (!getRadioValue(radiosIdeiaExistente)) {
      alert('Informe se a ideia já existe.');
      radiosIdeiaExistente[0]?.focus();
      return false;
    }

    if (!objetivo.value.trim()) {
      alert('Informe o objetivo estratégico.');
      objetivo.focus();
      return false;
    }

    if (!getRadioValue(radiosClassificacao)) {
      alert('Selecione a classificação da ideia.');
      radiosClassificacao[0]?.focus();
      return false;
    }

    return true;
  };

  const handleSubmit = (form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      if (!validateForm(form)) {
        return;
      }

      if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
      }

      const submitButton = form.querySelector('button[type="submit"]');
      const formData = new FormData(form);

      toggleSubmitState(submitButton, true);

      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        const text = await response.text();
        let data;

        try {
          data = JSON.parse(text);
        } catch (error) {
          console.error('Resposta inválida do servidor', text);
          throw new Error('Não foi possível interpretar a resposta do servidor.');
        }

        if (!response.ok || !data.success) {
          const message = data && data.message ? data.message : 'Falha ao criar a ideia.';
          throw new Error(message);
        }

        alert(data.message || 'Ideia criada com sucesso!');
        if (data.ticket_link) {
          window.location.href = data.ticket_link;
        }
      } catch (error) {
        alert(error.message || 'Erro ao enviar a ideia.');
      } finally {
        toggleSubmitState(submitButton, false);
      }
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-nova-ideia');
    if (!form) {
      return;
    }

    initTinyMCE();
    setupCampaignPreview(form);
    handleSubmit(form);
  });
})();
