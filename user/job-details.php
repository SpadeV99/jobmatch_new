// Add this button in job details:

<?php if (isset($job['employer_id'])): ?>
<a href="../messages/compose.php?recipient_id=<?php echo $job['employer_id']; ?>" class="btn btn-outline-primary">
    <i class="bi bi-chat-dots"></i> Contact Employer
</a>
<?php endif; ?>