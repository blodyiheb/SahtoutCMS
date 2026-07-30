                        document.getElementById('smtp_enabled').addEventListener('change', function() {
                            document.querySelector('.smtp-fields').classList.toggle('active', this.checked);
                        });