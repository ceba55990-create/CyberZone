
@extends('layouts.app')

@section('title', 'CyberZone — Головна')

@push('styles')
<style>
    .hero {
        min-height: 440px;
        padding: 100px 20px;
        background: linear-gradient(135deg, #19233d, #251740);
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .hero-content {
        max-width: 750px;
    }

    .hero-label {
        display: inline-block;
        padding: 9px 18px;
        border: 1px solid #7c3aed;
        border-radius: 30px;
        color: #c4b5fd;
        font-size: 12px;
        letter-spacing: 3px;
    }

    .hero h1 {
        font-size: clamp(32px, 5vw, 56px);
        margin: 25px 0;
    }

    .hero p {
        font-size: 18px;
        color: #cbd5e1;
        line-height: 1.7;
    }

    .hero-button {
        display: inline-block;
        margin-top: 25px;
        padding: 15px 30px;
        background: #7c3aed;
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: bold;
        transition: background 0.2s;
    }

    .hero-button:hover {
        background: #6d28d9;
    }

    .features {
        max-width: 1100px;
        margin: 0 auto;
        padding: 70px 20px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 25px;
    }

    .feature {
        padding: 30px;
        background: #19233a;
        border: 1px solid #30334f;
        border-radius: 12px;
        text-align: center;
    }

    .feature-icon {
        font-size: 36px;
    }

    .feature h3 {
        color: #c4b5fd;
    }

    .feature p {
        color: #94a3b8;
        line-height: 1.6;
    }

    @media (max-width: 700px) {
        .features {
            grid-template-columns: 1fr;
        }

        .hero {
            padding: 65px 20px;
        }
    }
</style>
@endpush

@section('content')

<section class="hero">
    <div class="hero-content">
        <span class="hero-label">GAMING CLUB</span>

        <h1>Твоя гра. Твої правила.</h1>

        <p>
            Ігрові комп'ютери, комфортні місця
            та атмосфера кіберспорту.
        </p>

        <a href="#computers" class="hero-button">
            Обрати комп'ютер →
        </a>
    </div>
</section>

<section class="features" id="computers">

    <div class="feature">
        <div class="feature-icon">🖥️</div>
        <h3>Ігрові комп'ютери</h3>
        <p>Потужні системи для сучасних ігор.</p>
    </div>

    <div class="feature">
        <div class="feature-icon">⏱️</div>
        <h3>Гнучкі тарифи</h3>
        <p>Погодинна оплата та ігрові пакети.</p>
    </div>

    <div class="feature">
        <div class="feature-icon">🎧</div>
        <h3>Максимальний комфорт</h3>
        <p>Зручні ігрові місця та обладнання.</p>
    </div>

</section>

@endsection
