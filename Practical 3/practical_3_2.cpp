#include<iostream>
using namespace std;
int main(){
    int arr[6]={2,0,1,2,0,1};
    int zero=0;
    int one=0;
    int two=0;
 
    for(int i=0;i<6;i++){
        if(arr[i]==0)
            zero++;
        else if(arr[i]==1)
            one++;
        else
            two++;
    }

    int idx=0;
    while(zero--){
        arr[idx]=0;
        idx++;
    }
   
    while(one--){
        arr[idx]=1;
        idx++;
    }

    while(two--){
        arr[idx]=2;
        idx++;
    }

    for(int i=0;i<6;i++){
        cout<<arr[i]<<" ";
    }
}