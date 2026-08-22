#include<iostream>
using namespace std;

int recursive(int ar[], int low, int high, int t){
    if(low > high){
        return -1;
   }
  int mid = low + (high - low)/2;
   if(ar[mid]==t){
    return ar[mid];
   }else if(ar[mid]>t){
    return recursive(ar,mid+1,high,t);
   }else{
    return recursive(ar,low,mid-1,t);
   }
}

int main(){
    int arr[6]={101, 102, 103, 104, 105, 106};
    int n=6;
    int target = 102;
    int low=0, high=n-1, mid;

    while(low <= high){
        mid = low + (high-low)/2;
        if(arr[mid]==target){
             break;
        }else if(arr[mid]<target){
           low = mid + 1;
        }else{
            high = mid - 1;
        }
      
    }
cout << "Itterative approach: " << endl;
    cout << arr[mid] << endl;
    cout << "Found at index: " << mid << endl;
    cout << "Found at Position: "  << mid + 1 << endl;

cout << "Recursive approach: " << endl;
cout << recursive(arr,low,high,target);
    return 0;
}