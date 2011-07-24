
var isDom = document.getElementById?true:false;
var isIE  = document.all?true:false;
var isNS4 = document.layers?true:false;
var cellCount = 10;

function setprogress(pIdent, pValue, pString, pDeterminate)
{
        if (isDom) {
            prog = document.getElementById(pIdent+'installationProgress');
        } else if (isIE) {
            prog = document.all[pIdent+'installationProgress'];
        } else if (isNS4) {
            prog = document.layers[pIdent+'installationProgress'];
        }
        if (prog != null) {
            prog.innerHTML = pString;
        }
        if (pValue == pDeterminate) {
            for (i=0; i < cellCount; i++) {
                showCell(i, pIdent, "hidden");	
            }
        }
        if ((pDeterminate > 0) && (pValue > 0)) {
            i = (pValue-1) % cellCount;
            showCell(i, pIdent, "visible");	
        } else {
            for (i=pValue-1; i >=0; i--) {
                showCell(i, pIdent, "visible");	
            }
        }
}

function showCell(pCell, pIdent, pVisibility)
{
        if (isDom) {
            document.getElementById(pIdent+'progressCell'+pCell+'A').style.visibility = pVisibility;
        } else if (isIE) {
            document.all[pIdent+'progressCell'+pCell+'A'].style.visibility = pVisibility;
        } else if (isNS4) {
            document.layers[pIdent+'progressCell'+pCell+'A'].style.visibility = pVisibility;
        }
}

