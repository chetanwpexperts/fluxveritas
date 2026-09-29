// Register page: onboarding type selector
function selectType(type) {
    var isOrg  = type === 'org_creator';
    var isTeam = type === 'team_member';
    document.getElementById('type-org').checked  = isOrg;
    document.getElementById('type-team').checked = isTeam;
    document.getElementById('card-org').style.borderColor  = isOrg  ? '#10b981' : '#27272a';
    document.getElementById('card-org').style.background   = isOrg  ? 'rgba(16,185,129,0.06)' : '#1a1a20';
    document.getElementById('card-team').style.borderColor = isTeam ? '#10b981' : '#27272a';
    document.getElementById('card-team').style.background  = isTeam ? 'rgba(16,185,129,0.06)' : '#1a1a20';
    document.getElementById('dot-org').style.borderColor   = isOrg  ? '#10b981' : '#27272a';
    document.getElementById('dot-org-fill').style.display  = isOrg  ? 'block' : 'none';
    document.getElementById('dot-team').style.borderColor  = isTeam ? '#10b981' : '#27272a';
    document.getElementById('dot-team-fill').style.display = isTeam ? 'block' : 'none';
    document.getElementById('token-field').style.display   = isTeam ? 'block' : 'none';
}
var saved = document.querySelector('input[name="onboarding_type"]:checked');
if (saved) selectType(saved.value);
