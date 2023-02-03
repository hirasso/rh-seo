
import './scss/rhseo.scss';

class RHSEO {
  constructor() {
    this.handleGeneralOptions();
  }

  handleGeneralOptions() {
    const isGeneralOptions = document.body.classList.contains('options-general-php');
    if (!isGeneralOptions) return;

    const inputBlogname = document.querySelector('input#blogname');
    const inputBlogdescription = document.querySelector('input#blogdescription');
    inputBlogname.setAttribute('readonly', 'readonly');
    inputBlogdescription.setAttribute('readonly', 'readonly');
    inputBlogname.setAttribute('disabled', 'disabled');
    inputBlogdescription.setAttribute('disabled', 'disabled');
  }
}
new RHSEO();