<?php
defined( 'ABSPATH' ) || exit;

$privacy = gl_opt( 'privacy_url' ) ?: get_privacy_policy_url();
?>
<section class="section kontakt" id="contact">
	<div class="kontakt__intro">
		<span class="meta" data-reveal>Contact</span>
		<h2 class="display-l" data-split>Get in<br>touch</h2>
		<?php if ( gl_opt( 'contact_intro' ) ) : ?><p data-reveal><?php echo esc_html( gl_opt( 'contact_intro' ) ); ?></p><?php endif; ?>
	</div>
	<form class="form" id="contact-form" novalidate data-reveal data-start="<?php echo esc_attr( time() ); ?>">
		<fieldset class="form__topics">
			<legend class="meta">Topic</legend>
			<label><input type="radio" name="topic" value="booking" id="t-booking" checked><span>Booking</span></label>
			<label><input type="radio" name="topic" value="press" id="t-press"><span>Press</span></label>
			<label><input type="radio" name="topic" value="general" id="t-general"><span>General</span></label>
		</fieldset>
		<label class="form__field"><span class="meta">Name</span><input name="name" id="f-name" required maxlength="120" autocomplete="name"></label>
		<label class="form__field"><span class="meta">Email</span><input name="email" id="f-email" type="email" required autocomplete="email"></label>
		<label class="form__field"><span class="meta">Message</span><textarea name="message" id="f-message" rows="5" required maxlength="5000"></textarea></label>
		<label class="visually-hidden" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>
		<label class="form__check"><input type="checkbox" id="f-privacy" required><span>I agree that my data is used to answer my request.<?php if ( $privacy ) : ?> <a href="<?php echo esc_url( $privacy ); ?>">Privacy ↗</a><?php endif; ?></span></label>
		<button class="btn btn--hell form__submit" type="submit">Send message <span class="pfeil">↗</span></button>
		<p class="form__status meta meta--hell" role="status" aria-live="polite"></p>
	</form>
</section>
