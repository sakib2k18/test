// Simple client-side validation helpers
document.addEventListener('DOMContentLoaded', function(){
  const forms = document.querySelectorAll('form.needs-validation');
  Array.prototype.slice.call(forms).forEach(function(form){
    form.addEventListener('submit', function(e){
      if (!form.checkValidity()){
        e.preventDefault(); e.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });
});
