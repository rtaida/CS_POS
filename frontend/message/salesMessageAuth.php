<?php if(isset($_GET['receipt_saved'])): ?>
<script>
Swal.fire({ 
    icon: 'success', 
    title: 'Sale Completed!', 
    text: 'Transaction has been completed successfully.' 
}).then(() => { 
    window.history.replaceState({}, document.title, window.location.pathname); 
});
</script>
<?php endif; ?>

<?php if(isset($_GET['sale_started'])): ?>
<script>
Swal.fire({ 
    icon: 'success', 
    title: 'New Sale Started!', 
    text: 'You can now add items to this sale.' 
}).then(() => { 
    window.history.replaceState({}, document.title, window.location.pathname); 
});
</script>
<?php endif; ?>