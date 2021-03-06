#!/bin/bash
#
# Developed by Fred Weinhaus 10/29/2008 .......... revised 10/29/2008
#
# USAGE: otsuthresh [-g graph] infile outfile
# USAGE: otsuthresh [-help]
#
# OPTIONS:
#
# -g	  graph             graph specifies whether to generate a 
#                           histogram graph image displaying the 
#                           location and value of the threshold;
#                           choices are: view or save; 
#                           default is no graph
#
###
#
# NAME: OTSUTHRESH
# 
# PURPOSE: To automatically thresholds an image to binary (b/w) format 
# using Otsu's between class variance technique.
# 
# DESCRIPTION: OTSUTHRESH automatically thresholds an image to binary
# (b/w) format. It assume the histogram is bimodal, i.e. is the composite
# of two bell-shaped distributions representing the foreground and
# background classes. The Otsu appoach computes the Between Class Variance 
# from the foreground (above threshold data) and background (at and below 
# threshold value) for every possible threshold value. The optimal threshold 
# is the one that maximizes the Between Class Variance. This is equivalent 
# to finding the threshold that minimizes the overlap between the two 
# bell-shaped class curves.
# 
# OPTIONS: 
# 
# -g graph ... GRAPH specifies whether to generate a graph (image) of 
# the histogram, displaying the location and value of the threshold. 
# The choices are: view, save and none. If graph=view is selected, the 
# graph will be created and displayed automatically, but not saved. 
# If graph=save is selected, then the graph will be created and saved 
# to a file using the infile name, with "_histog_otsu.gif" appended,  
# but the graph will not be displayed automatically. If -g option is 
# not specified, then no graph will be created.
# 
# NOTE: It is highly recommended that the output not be specified 
# as a JPG image as that will cause compression and potentially a 
# non-binary (i.e. a graylevel) result. GIF is the recommended 
# output format.
# 
# REFERENCES: see the following:
# http://www.ph.tn.tudelft.nl/Courses/FIP/noframes/fip-Segmenta.html
# http://homepages.inf.ed.ac.uk/rbf/CVonline/LOCAL_COPIES/MORSE/threshold.pdf
# http://www.cse.unr.edu/~bebis/CS791E/Notes/Thresholding.pdf
# http://www.ifi.uio.no/in384/info/threshold.ps
# http://www.supelec.fr/ecole/radio/JPPS01.pdf
# 
# CAVEAT: No guarantee that this script will work on all platforms, 
# nor that trapping of inconsistent parameters is complete and 
# foolproof. Use At Your Own Risk. 
# 
######
#

# set default values
graph=""		#none, save or view

# set directory for temporary files
dir="."    # suggestions are dir="." or dir="/tmp"

# set up functions to report Usage and Usage with Description
PROGNAME=`type $0 | awk '{print $3}'`  # search for executable on path
PROGDIR=`dirname $PROGNAME`            # extract directory of program
PROGNAME=`basename $PROGNAME`          # base name of program
usage1() 
	{
	echo >&2 ""
	echo >&2 "$PROGNAME:" "$@"
	sed >&2 -n '/^###/q;  /^#/!q;  s/^#//;  s/^ //;  4,$p' "$PROGDIR/$PROGNAME"
	}
usage2() 
	{
	echo >&2 ""
	echo >&2 "$PROGNAME:" "$@"
	sed >&2 -n '/^######/q;  /^#/!q;  s/^#*//;  s/^ //;  4,$p' "$PROGDIR/$PROGNAME"
	}


# function to report error messages
errMsg()
	{
	echo ""
	echo $1
	echo ""
	usage1
	exit 1
	}


# function to test for minus at start of value of second part of option 1 or 2
checkMinus()
	{
	test=`echo "$1" | grep -c '^-.*$'`   # returns 1 if match; 0 otherwise
    [ $test -eq 1 ] && errMsg "$errorMsg"
	}

# test for correct number of arguments and get values
if [ $# -eq 0 ]
	then
	# help information
   echo ""
   usage2
   exit 0
elif [ $# -gt 4 ]
	then
	errMsg "--- TOO MANY ARGUMENTS WERE PROVIDED ---"
else
	while [ $# -gt 0 ]
		do
			# get parameter values
			case "$1" in
		  -h|-help)    # help information
					   echo ""
					   usage2
					   exit 0
					   ;;
				-g)    # get  graph
					   shift  # to get the next parameter
					   # test if parameter starts with minus sign 
					   errorMsg="--- INVALID GRAPH SPECIFICATION ---"
					   checkMinus "$1"
					   graph="$1"
					   [ "$graph" != "view" -a "$graph" != "save" ] && errMsg "--- GRAPH=$graph MUST BE EITHER VIEW OR SAVE ---"
					   ;;
				 -)    # STDIN and end of arguments
					   break
					   ;;
				-*)    # any other - argument
					   errMsg "--- UNKNOWN OPTION ---"
					   ;;
		     	 *)    # end of arguments
					   break
					   ;;
			esac
			shift   # next option
	done
	#
	# get infile and outfile
	infile=$1
	outfile=$2
fi

# test that infile provided
[ "$infile" = "" ] && errMsg "NO INPUT FILE SPECIFIED"

# test that outfile provided
[ "$outfile" = "" ] && errMsg "NO OUTPUT FILE SPECIFIED"

# get outname from infile to use for graph
inname=`convert $infile -format "%t" info:`
histfile=${inname}_histog_otsu.gif

tmpA1="$dir/otsuthresh_1_$$.mpc"
tmpA2="$dir/otsuthresh_1_$$.cache"
trap "rm -f $tmpA1 $tmpA2; exit 0" 0
trap "rm -f $tmpA1 $tmpA2; exit 1" 1 2 3 15

if convert -quiet -regard-warnings "$infile" -colorspace Gray +repage "$tmpA1"
	then
	: ' do nothing '
else
	errMsg "--- FILE $infile DOES NOT EXIST OR IS NOT AN ORDINARY FILE, NOT READABLE OR HAG ZERO SIZE ---"
fi	

# get totpix in image
width=`convert $tmpA1 -format "%w" info:`
height=`convert $tmpA1 -format "%h" info:`
totpix=`echo "scale=0; $width * $height / 1" | bc`

# get im version
im_version=`convert -list configure | \
sed '/^LIB_VERSION_NUMBER /!d;  s//,/;  s/,/,0/g;  s/,0*\([0-9][0-9]\)/\1/g'`

# function to convert IM histogram into two arrays, value and count
getHistog()
	{
	echo "Generate Histogram"
	img="$1"
	# get lists of values and counts from histogram
	# note that IM histograms are not well sorted (and have multiple bins with counts for the same values)
	value=`convert $img -format %c -depth 8 histogram:info: | sort -k 2 -b | sed -n 's/^ *[0-9]*: [(]\([0-9 ]*\).*$/\1/ p'`
	count=`convert $img -format %c -depth 8 histogram:info: | sort -k 2 -b | sed -n 's/^ *\([0-9]*\): [(].*$/\1/ p'`
	
	# put value and count into arrays
	valueArr=($value)
	countArr=($count)
	
	# check if both arrays are the same size
	if [ ${#valueArr[*]} -ne ${#countArr[*]} ]
		then
			errMsg "--- ARRAY SIZES DO NOT MATCH ---"
			exit 1
		else
		numbins=${#valueArr[*]}
#echo "numbins=$numbins"
	fi
}

# function to normalize histog
getNormlizedHistog()
	{
	echo "Normalize Histogram"
	j=0
	while [ $j -lt $numbins ]; do
		countArr[$j]=`echo "scale=10; ${countArr[$j]}/$totpix" | bc`
		j=`expr $j + 1`
	done
	}

getGlobalMean()
	{
	echo "Compute Global Mean"
	i=0
	mean=0
	while [ $i -lt $numbins ]; do
		mean=`echo "scale=10; $mean + ${valueArr[$i]}*${countArr[$i]}" | bc`
		i=`expr $i + 1`
	done
	}


# process image using Otsu's approach

echo ""
getHistog "$tmpA1"
getNormlizedHistog
getGlobalMean

echo "Generate Cumulative Arrays"
# p=c(i)/N (normalized count or probability, p, at bin i)
# v=v(i) (graylevel at bin i)
# t=threshold bin
# n=p0=sum(c(i)/N)zeroth histogram moment => cumulative normalized count (from i=0 to t) = N(t)
# g=p1=sum(c(i)*v(i))=first histogram momement => cumulative normalized graylevel (from i=0 to t) = G(t)

i=0
nlow=0
nhigh=0
glow=0
ghigh=0
while [ $i -lt $numbins ]; do
	nlow=`echo "scale=10; $nlow + ${countArr[$i]}" | bc`
	nlowArr[$i]=$nlow
	glow=`echo "scale=10; $glow + ${valueArr[$i]}*${countArr[$i]}" | bc`
	glowArr[$i]=$glow
#echo "i=$i; j=$j; nlow=${nlowArr[$i]}; glow=${glowArr[$i]}"
	i=`expr $i + 1`
done


echo "Compute Threshold"
# loop through histogram using normalized counts and values
# compute threshold by maximizing between class variance
# bcv=Nl*(Ml-M)^2 + Nh*(Mh-M)^2 = (M*Nl-Gl)^2/(Nl*(1-Nl))

# m=p1/p0=mean => M(t)=G(t)/N(t) 
# where Nh and Nl are normalized counts above and below threshold (zeroth moments, p0)
# Mh and Ml are means of pixels above and below threshold
# Gh and Gl are normalized cumulative graylevels (first moments, p1)
# ML=Gl/Nl
# above derived using
# Nh=(1-Nl) for normalized histogram
# Mh=(M-Ml*Nl)/(1-Nl) where M is overall mean of image

i=0
bcvold=0
threshbin=0
#note: must stop at second to last bin so that nlow != 1 exactly or get divide by zero
lastbin=`expr $numbins - 1`
while [ $i -lt $lastbin ]; do
	nlow=${nlowArr[$i]}
	glow=${glowArr[$i]}
	dmean=`echo "scale=10; ($mean*$nlow - $glow)" | bc`
	bcv=`echo "scale=10; $dmean*$dmean/($nlow*(1-$nlow))" | bc`
	test=`echo "$bcv > $bcvold" | bc`
	if [ $test -eq 1 ]; then
		bcvold=$bcv
		threshbin=$i
	fi
#echo "i=$i; nlow=$nlow; glow=$glow; bcv=$bcv; bcvold=$bcvold; test=$test; threshbin=$threshbin"
	i=`expr $i + 1`
done
thresh=${valueArr[$threshbin]}


# compute threshold graph x coord and threshold in percent
xx=$thresh
threshpct=`convert xc: -format "%[fx:100*$thresh/255]" info:`
#echo "xx=$xx; threshpct=$threshpct"


echo "Thresholding Image At $threshpct%"
convert $tmpA1 -threshold $threshpct% $outfile
echo ""


if [ "$graph" != "" ]; then
	convert $tmpA1 histogram:- | \
		convert - -negate \
		-stroke red -strokewidth 1 -draw "line $xx,0 $xx,200" \
		-background gray -splice 0x30 \
		-fill white -stroke white -strokewidth 1 \
		-font ArialB -pointsize 24 \
		-draw "text 4,22 'threshold=$threshpct%'" -resize 50% \
		-bordercolor gray50 -border 5 \
		$histfile
	trap "rm -f $histfile; exit 1" 1 2 3 15
fi

if [ "$graph" = "view" ]; then
	convert $histfile x:
	trap "rm -f $histfile; exit 0" 0
	trap "rm -f $histfile; exit 1" 1 2 3 15
fi

exit 0



