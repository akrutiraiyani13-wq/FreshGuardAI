<?php include 'includes/header.php'; ?>

<!-- Hero overlay with background image -->
<section class="hero" style="background-image: linear-gradient(rgba(0,0,0,0.65), rgba(0,0,0,0.65)), url('https://images.unsplash.com/photo-1543362906-acfc16c67564?auto=format&fit=crop&w=1920&q=80'); background-size: cover; background-position: center; padding: 180px 0 200px; color: white;">
    <div class="container text-center">
        <span style="font-style: italic; font-size: 1.8rem; color: var(--warning-yellow);">Welcome To</span>
        <h1 style="color: white; font-size: 4.5rem; margin: 10px 0; text-transform: uppercase; letter-spacing: 2px;">FreshGuardAI</h1>
        <div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 40px;">
            <div style="width: 60px; height: 1px; background: rgba(255,255,255,0.4);"></div>
            <span style="font-size: 0.9rem; letter-spacing: 3px; text-transform: uppercase;">Venue of the Best Food Management</span>
            <div style="width: 60px; height: 1px; background: rgba(255,255,255,0.4);"></div>
        </div>
        
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="register.php" class="btn" style="background: transparent; border: 2px solid var(--warning-yellow); color: var(--warning-yellow) !important; font-size: 1.1rem; padding: 12px 35px; border-radius: 0; font-weight: 600;">VIEW DEMO</a>
        <?php else: ?>
            <a href="dashboard.php" class="btn" style="background: transparent; border: 2px solid var(--warning-yellow); color: var(--warning-yellow) !important; font-size: 1.1rem; padding: 12px 35px; border-radius: 0; font-weight: 600;">GO TO DASHBOARD</a>
        <?php endif; ?>
    </div>
</section>

<!-- Overlapping Cards Section -->
<section style="margin-top: -100px; position: relative; z-index: 10;">
    <div class="container grid" style="gap: 30px;">
        <div class="card text-center" style="border-radius: 0; padding: 50px 30px; box-shadow: 0 15px 40px rgba(0,0,0,0.15); border: none;">
            <div style="font-size: 2.5rem; color: var(--warning-yellow); margin-bottom: 20px;">🛒</div>
            <h3 style="font-size: 1.3rem; margin-bottom: 15px; font-weight: 700;">Track Everything</h3>
            <p style="color: #666; font-size: 0.95rem;">Maintain a complete digital inventory of your pantry and fridge so you never double-buy food.</p>
        </div>
        <div class="card text-center" style="border-radius: 0; padding: 50px 30px; box-shadow: 0 15px 40px rgba(0,0,0,0.15); border: none;">
            <div style="font-size: 2.5rem; color: var(--warning-yellow); margin-bottom: 20px;">⏱️</div>
            <h3 style="font-size: 1.3rem; margin-bottom: 15px; font-weight: 700;">Use Soon Reminders</h3>
            <p style="color: #666; font-size: 0.95rem;">Get notified when ingredients are close to expiry and get relevant recipe suggestions!</p>
        </div>
        <div class="card text-center" style="border-radius: 0; padding: 50px 30px; box-shadow: 0 15px 40px rgba(0,0,0,0.15); border: none;">
            <div style="font-size: 2.5rem; color: var(--warning-yellow); margin-bottom: 20px;">🗑️</div>
            <h3 style="font-size: 1.3rem; margin-bottom: 15px; font-weight: 700;">Stop the Waste</h3>
            <p style="color: #666; font-size: 0.95rem;">Know exactly what is expired and safely dispose of it, automatically adding it to replacements.</p>
        </div>
    </div>
</section>

<section class="section container" style="margin-top: 80px; margin-bottom: 60px;">
    <div style="display: flex; gap: 60px; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 320px;">
            <h2 style="font-size: 2.8rem; margin-bottom: 25px; color: var(--text-dark);">Discover <span style="color: var(--brown);">Our Story</span></h2>
            <p style="color: #666; line-height: 1.8; margin-bottom: 40px; font-size: 1.05rem;">
                Did you know the average family throws away thousands of dollars in unused food every year simply because they forgot it was in the fridge?
                <br><br>
                FreshGuardAI was born out of a simple observation: we buy food with good intentions, but our busy lives cause us to forget what we have. It gets shoved to the back of the fridge and expires. FreshGuardAI prevents this by helping you remember your food before it becomes waste.
            </p>
            <div style="display: flex; justify-content: space-between; text-align: center; border-top: 1px solid #eee; padding-top: 30px;">
                <div>
                    <h3 style="font-size: 2.2rem; color: var(--text-dark); margin: 0;">1.05B</h3>
                    <p style="color: #888; text-transform: uppercase; font-size: 0.85rem; font-weight: 600; letter-spacing: 1px;">Tonnes Wasted</p>
                </div>
                <div>
                    <h3 style="font-size: 2.2rem; color: var(--text-dark); margin: 0;">60%</h3>
                    <p style="color: #888; text-transform: uppercase; font-size: 0.85rem; font-weight: 600; letter-spacing: 1px;">From Homes</p>
                </div>
                <div>
                    <h3 style="font-size: 2.2rem; color: var(--text-dark); margin: 0;">$1500</h3>
                    <p style="color: #888; text-transform: uppercase; font-size: 0.85rem; font-weight: 600; letter-spacing: 1px;">Avg Loss</p>
                </div>
            </div>
        </div>
        <div style="flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; min-width: 320px;">
            <img src="https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=600&q=80" alt="Fresh Food" style="width: 100%; height: 100%; object-fit: cover; border-radius: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=80" alt="Healthy Meal" style="width: 100%; height: 100%; object-fit: cover; border-radius: 0; transform: translateY(40px); box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
