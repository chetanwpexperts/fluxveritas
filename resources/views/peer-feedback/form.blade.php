<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peer Feedback — OutraqHQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/peer-feedback.css') }}">
</head>
<body>
<div class="peer-wrap">

    <div class="peer-header">
        <div class="peer-logo">
            <span class="peer-logo-mark">OQ</span>
            OutraqHQ
        </div>
        <div class="peer-badge">Anonymous Review</div>
    </div>

    <div class="peer-card">
        <div class="peer-card-header">
            <div class="peer-avatar">
                {{ strtoupper(substr($employee->name, 0, 1)) }}
            </div>
            <div class="peer-intro">
                <h1 class="peer-title">
                    Peer Feedback for <span class="peer-name">{{ $employee->name }}</span>
                </h1>
                <p class="peer-subtitle">
                    Your feedback is completely anonymous. No one will know it came from you. Takes less than 2 minutes.
                </p>
            </div>
        </div>

        @if($errors->any())
        <div class="peer-errors">
            @foreach($errors->all() as $error)
            <p class="peer-error">{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('peer-feedback.submit', $token) }}">
            @csrf

            <div class="peer-ratings">

                <div class="peer-rating-row">
                    <div class="peer-rating-label">
                        <span class="peer-rating-icon">🤝</span>
                        <div>
                            <div class="peer-rating-title">Collaboration</div>
                            <div class="peer-rating-desc">Works well with others</div>
                        </div>
                    </div>
                    <div class="peer-stars">
                        @for($i = 1; $i <= 5; $i++)
                        <input type="radio" name="collaboration_score" value="{{ $i }}" id="collab_{{ $i }}" class="peer-star-input" {{ old('collaboration_score') == $i ? 'checked' : '' }}>
                        <label for="collab_{{ $i }}" class="peer-star-label">★</label>
                        @endfor
                    </div>
                </div>

                <div class="peer-rating-row">
                    <div class="peer-rating-label">
                        <span class="peer-rating-icon">⏰</span>
                        <div>
                            <div class="peer-rating-title">Reliability</div>
                            <div class="peer-rating-desc">Delivers on commitments</div>
                        </div>
                    </div>
                    <div class="peer-stars">
                        @for($i = 1; $i <= 5; $i++)
                        <input type="radio" name="reliability_score" value="{{ $i }}" id="reliability_{{ $i }}" class="peer-star-input" {{ old('reliability_score') == $i ? 'checked' : '' }}>
                        <label for="reliability_{{ $i }}" class="peer-star-label">★</label>
                        @endfor
                    </div>
                </div>

                <div class="peer-rating-row">
                    <div class="peer-rating-label">
                        <span class="peer-rating-icon">🧠</span>
                        <div>
                            <div class="peer-rating-title">Knowledge</div>
                            <div class="peer-rating-desc">Technical/domain expertise</div>
                        </div>
                    </div>
                    <div class="peer-stars">
                        @for($i = 1; $i <= 5; $i++)
                        <input type="radio" name="knowledge_score" value="{{ $i }}" id="knowledge_{{ $i }}" class="peer-star-input" {{ old('knowledge_score') == $i ? 'checked' : '' }}>
                        <label for="knowledge_{{ $i }}" class="peer-star-label">★</label>
                        @endfor
                    </div>
                </div>

                <div class="peer-rating-row">
                    <div class="peer-rating-label">
                        <span class="peer-rating-icon">💪</span>
                        <div>
                            <div class="peer-rating-title">Helpfulness</div>
                            <div class="peer-rating-desc">Helps when others need it</div>
                        </div>
                    </div>
                    <div class="peer-stars">
                        @for($i = 1; $i <= 5; $i++)
                        <input type="radio" name="helpfulness_score" value="{{ $i }}" id="helpfulness_{{ $i }}" class="peer-star-input" {{ old('helpfulness_score') == $i ? 'checked' : '' }}>
                        <label for="helpfulness_{{ $i }}" class="peer-star-label">★</label>
                        @endfor
                    </div>
                </div>

            </div>

            <div class="peer-text-section">
                <div class="peer-text-group">
                    <label class="peer-text-label">
                        One thing they do really well
                        <span class="peer-optional">(optional)</span>
                    </label>
                    <textarea name="strength" class="peer-textarea" rows="2" maxlength="200"
                        placeholder="e.g. Always helps the team when stuck...">{{ old('strength') }}</textarea>
                </div>
                <div class="peer-text-group">
                    <label class="peer-text-label">
                        One area they could improve
                        <span class="peer-optional">(optional)</span>
                    </label>
                    <textarea name="improvement" class="peer-textarea" rows="2" maxlength="200"
                        placeholder="e.g. Could communicate blockers earlier...">{{ old('improvement') }}</textarea>
                </div>
            </div>

            <div class="peer-anonymous-note">
                🔒 This feedback is completely anonymous. Your identity will never be revealed.
            </div>

            <button type="submit" class="peer-submit-btn">
                Submit Anonymous Feedback →
            </button>

        </form>
    </div>

    <div class="peer-footer">
        Powered by OutraqHQ · Building fair workplaces with AI
    </div>

</div>
</body>
</html>
